<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Cache\ResultCacheHelper;
use Mautic\CoreBundle\Cache\ResultCacheOptions;
use Mautic\LeadBundle\Controller\ListController;
use Mautic\LeadBundle\Helper\CustomFieldHelper;

trait CustomFieldRepositoryTrait
{
    protected $useDistinctCount = false;

    /**
     * @var array
     */
    protected $customFieldList = [];

    /**
     * @var string
     */
    protected $uniqueIdentifiersOperator;

    /**
     * @param string $object
     * @param array  $args
     */
    public function getEntitiesWithCustomFields($object, $args, $resultsCallback = null)
    {
        $skipOrdering           = $args['skipOrdering'] ?? false;
        [$fields, $fixedFields] = $this->getCustomFieldList($object);

        // Fix arguments if necessary
        $args = $this->convertOrmProperties($this->getClassName(), $args);

        // DBAL
        /** @var QueryBuilder $dq */
        $dq = $args['qb'] ?? $this->getEntitiesDbalQueryBuilder();

        // Generate where clause first to know if we need to use distinct on primary ID or not
        $this->useDistinctCount = false;
        $this->buildWhereClause($dq, $args);

        if (!empty($args['withTotalCount']) || !isset($args['count'])) {
            // Distinct is required here to get the correct count when group by is used due to applied filters
            $countSelect = ($this->useDistinctCount) ? 'COUNT(DISTINCT('.$this->getTableAlias().'.id))' : 'COUNT('.$this->getTableAlias().'.id)';
            $dq->select($countSelect.' as count');

            // Advanced search filters may have set a group by and if so, let's remove it for the count.
            if ($groupBy = $dq->getQueryPart('groupBy')) {
                $dq->resetQueryPart('groupBy');
            }

            // get a total count
            if (!empty($args['totalCountTtl'])) {
                $statement = ResultCacheHelper::executeCachedDbalQuery($this->getEntityManager()->getConnection(), $dq, new ResultCacheOptions($object.'-total-count', $args['totalCountTtl']));
            } else {
                $statement = $dq->executeQuery();
            }

            $result = $statement->fetchAllAssociative();
            $total  = ($result) ? $result[0]['count'] : 0;
        } else {
            $total = $args['count'];
        }

        if (!$total && !empty($args['withTotalCount'])) {
            $results = [];
        } else {
            if (isset($groupBy) && $groupBy) {
                $dq->groupBy($groupBy);
            }
            // now get the actual paginated results

            $this->buildOrderByClause($dq, $args);
            $this->buildLimiterClauses($dq, $args);

            $dq->resetQueryPart('select');
            $this->buildSelectClause($dq, $args);

            $results = $dq->executeQuery()->fetchAllAssociative();
            if (isset($args['route']) && ListController::ROUTE_SEGMENT_CONTACTS == $args['route']) {
                unset($args['select']); // Our purpose of getting list of ids has already accomplished. We no longer need this.
            }

            // loop over results to put fields in something that can be assigned to the entities
            $fieldValues = [];
            $groups      = $this->getFieldGroups();

            foreach ($results as $result) {
                $id = $result['id'];
                // unset all the columns that are not fields
                $this->removeNonFieldColumns($result, $fixedFields);

                foreach ($result as $k => $r) {
                    if (isset($fields[$k])) {
                        $fieldValues[$id][$fields[$k]['group']][$fields[$k]['alias']]          = $fields[$k];
                        $fieldValues[$id][$fields[$k]['group']][$fields[$k]['alias']]['value'] = $r;
                    }
                }

                // make sure each group key is present
                foreach ($groups as $g) {
                    if (!isset($fieldValues[$id][$g])) {
                        $fieldValues[$id][$g] = [];
                    }
                }
            }

            unset($results, $fields);

            // get an array of IDs for ORM query
            $ids = array_keys($fieldValues);

            if (count($ids)) {
                if ($skipOrdering) {
                    $alias = $this->getTableAlias();
                    $q     = $this->getEntityManager()->createQueryBuilder();
                    $q->select($alias)
                        ->from(Lead::class, $alias, $alias.'.id')
                        ->indexBy($alias, $alias.'.id');
                } else {
                    // ORM

                    // build the order by id since the order was applied above
                    // unfortunately, doctrine does not have a way to natively support this and can't use MySQL's FIELD function
                    // since we have to be cross-platform; it's way ugly

                    // We should probably totally ditch orm for leads

                    // This "hack" is in place to allow for custom ordering in the API.
                    // See https://github.com/mautic/mautic/pull/7494#issuecomment-600970208
                    $order = '(CASE';
                    foreach ($ids as $count => $id) {
                        $order .= ' WHEN '.$this->getTableAlias().'.id = '.$id.' THEN '.$count;
                        ++$count;
                    }
                    $order .= ' ELSE '.$count.' END) AS HIDDEN ORD';

                    // ORM - generates lead entities
                    /** @var \Doctrine\ORM\QueryBuilder $q */
                    $q = $this->getEntitiesOrmQueryBuilder($order, $args);
                    $this->buildSelectClause($dq, $args);

                    $q->orderBy('ORD', Order::Ascending->value);
                }

                // only pull the leads as filtered via DBAL
                $q->where(
                    $q->expr()->in($this->getTableAlias().'.id', ':entityIds')
                )->setParameter('entityIds', $ids);

                $results = $q->getQuery()
                    ->useQueryCache(false) // the query contains ID's, so there is no use in caching it
                    ->getResult();

                // assign fields
                /** @var Lead $r */
                foreach ($results as $r) {
                    $id = $r->getId();
                    $r->setFields($fieldValues[$id]);

                    if (is_callable($resultsCallback)) {
                        $resultsCallback($r);
                    }
                }
            } else {
                $results = [];
            }
        }

        return (!empty($args['withTotalCount'])) ?
            [
                'count'   => $total,
                'results' => $results,
            ] : $results;
    }

    /**
     * @param bool   $byGroup
     * @param string $object
     *
     * @return array
     */
    public function getFieldValues($id, $byGroup = true, $object = 'lead')
    {
        // use DBAL to get entity fields
        $q = $this->getEntitiesDbalQueryBuilder();

        if (is_array($id)) {
            $this->buildSelectClause($q, $id);
            $id = $id['id'];
        } else {
            $q->select($this->getTableAlias().'.*');
        }

        $q->where($this->getTableAlias().'.id = '.(int) $id);
        $values = $q->executeQuery()->fetchAssociative();

        return $this->formatFieldValues($values, $byGroup, $object);
    }

    /**
     * Gets a list of unique values from fields for autocompletes.
     *
     * @param string $search
     * @param int    $limit
     * @param int    $start
     *
     * @return array
     */
    public function getValueList($field, $search = '', $limit = 10, $start = 0)
    {
        // Includes prefix
        $table = $this->getEntityManager()->getClassMetadata($this->getClassName())->getTableName();
        $col   = $this->getTableAlias().'.'.$field;
        $q     = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select("DISTINCT $col")
            ->from($table, 'l');

        $q->where(
            $q->expr()->and(
                $q->expr()->neq($col, $q->expr()->literal('')),
                $q->expr()->isNotNull($col)
            )
        );

        if (!empty($search)) {
            $q->andWhere("$col LIKE :search")
                ->setParameter('search', "{$search}%");
        }

        $q->orderBy($col);

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * Persist an array of entities.
     *
     * @param array $entities
     */
    public function saveEntities($entities): void
    {
        foreach ($entities as $entity) {
            // Leads cannot be batched due to requiring the ID to update the fields
            $this->saveEntity($entity);
        }
    }

    public function saveEntity($entity, $flush = true): void
    {
        $this->preSaveEntity($entity);

        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush($entity);
        }

        // Includes prefix
        $table  = $this->getEntityManager()->getClassMetadata($this->getClassName())->getTableName();
        $fields = $entity->getUpdatedFields();
        if (method_exists($entity, 'getChanges')) {
            $changes = $entity->getChanges();

            // remove the fields that are part of changes as they were already saved via a setter
            $fields = array_diff_key($fields, $changes);
        }

        $this->prepareDbalFieldsForSave($fields);

        if (!empty($fields)) {
            $this->getEntityManager()->getConnection()->update($table, $fields, ['id' => $entity->getId()]);
        }

        $this->postSaveEntity($entity);
    }

    /**
     * Function to remove non custom field columns from an arrayed lead row.
     *
     * @param array $fixedFields
     */
    protected function removeNonFieldColumns(&$r, $fixedFields = [])
    {
        $baseCols = $this->getBaseColumns($this->getClassName(), true);
        foreach ($baseCols as $c) {
            if (!isset($fixedFields[$c])) {
                unset($r[$c]);
            }
        }
        unset($r['owner_id']);
    }

    /**
     * @param array  $values
     * @param bool   $byGroup
     * @param string $object
     */
    protected function formatFieldValues($values, $byGroup = true, $object = 'lead'): array
    {
        [$fields, $fixedFields] = $this->getCustomFieldList($object);

        $this->removeNonFieldColumns($values, $fixedFields);

        // Reorder leadValues based on field order
        $values = array_merge(array_flip(array_keys($fields)), $values);

        $fieldValues = [];

        // loop over results to put fields in something that can be assigned to the entities
        foreach ($values as $k => $r) {
            if (isset($fields[$k])) {
                $r = CustomFieldHelper::fixValueType($fields[$k]['type'], $r);

                if (!is_null($r)) {
                    switch ($fields[$k]['type']) {
                        case 'number':
                            $r = (float) $r;
                            break;
                        case 'boolean':
                            $r = (int) $r;
                            break;
                    }
                }

                $alias = $fields[$k]['alias'];

                if ($byGroup) {
                    $group                                = $fields[$k]['group'];
                    $fieldValues[$group][$alias]          = $fields[$k];
                    $fieldValues[$group][$alias]['value'] = $r;
                } else {
                    $fieldValues[$alias]          = $fields[$k];
                    $fieldValues[$alias]['value'] = $r;
                }

                unset($fields[$k]);
            }
        }

        if ($byGroup) {
            // make sure each group key is present
            $groups = $this->getFieldGroups();
            foreach ($groups as $g) {
                if (!isset($fieldValues[$g])) {
                    $fieldValues[$g] = [];
                }
            }
        }

        return $fieldValues;
    }

    /**
     * Retrieves the aliases of searchable fields that are indexed and published.
     *
     * @return array<int, string>
     */
    public function getSearchableFieldAliases(LeadFieldRepository $leadFieldRepository, string $object): array
    {
        return $leadFieldRepository->getSearchableFieldAliases($object);
    }

    /**
     * @param string $object
     *
     * @return array [$fields, $fixedFields]
     */
    public function getCustomFieldList($object)
    {
        if (empty($this->customFieldList)) {
            // Get the list of custom fields
            $results = $this->getFieldList($object);

            $fields      = [];
            $fixedFields = [];
            foreach ($results as $r) {
                $fields[$r['alias']] = $r;
                if ($r['is_fixed']) {
                    $fixedFields[$r['alias']] = $r['alias'];
                }
            }

            $this->customFieldList = [$fields, $fixedFields];
        }

        return $this->customFieldList;
    }

    protected function prepareDbalFieldsForSave(&$fields)
    {
        // Ensure booleans are integers
        foreach ($fields as $field => &$value) {
            if (is_bool($value)) {
                $fields[$field] = (int) $value;
            }
        }
    }

    /**
     * @return array<array<int|string>|int|string>
     *
     * @throws Exception
     */
    private function getFieldList(string $object = null): array
    {
        // Get the list of custom fields
        $fq = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $fq->select(
            'f.id, f.label, f.alias, f.type, f.field_group as "group", f.object, f.is_fixed, f.properties, f.default_value'
        )
            ->from(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
            ->where('f.is_published = :published')
            ->setParameter('published', true, 'boolean')
            ->addOrderBy('f.field_order', 'asc');

        if (null !== $object) {
            $fq->andWhere($fq->expr()->eq('object', ':object'))
                ->setParameter('object', $object);
        }

        return $fq->executeQuery()->fetchAllAssociative() ?: [];
    }

    /**
     * Inherit and use in class if required to do something to the entity prior to persisting.
     */
    protected function preSaveEntity($entity)
    {
        // Inherit and use if required
    }

    /**
     * Inherit and use in class if required to do something with the entity after persisting.
     */
    protected function postSaveEntity($entity)
    {
        // Inherit and use if required
    }

    public function setUniqueIdentifiersOperator(string $uniqueIdentifiersOperator): void
    {
        $this->uniqueIdentifiersOperator = $uniqueIdentifiersOperator;
    }

    public function getUniqueIdentifiersWherePart(): string
    {
        if ($this->uniqueIdentifiersOperatorIs(CompositeExpression::TYPE_AND)) {
            return 'andWhere';
        }

        return 'orWhere';
    }

    private function uniqueIdentifiersOperatorIs(string $operator): bool
    {
        return $this->uniqueIdentifiersOperator === $operator;
    }
}
