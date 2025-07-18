<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Api\Zoho;

use MauticPlugin\MauticCrmBundle\Api\Zoho\Exception\MatchingKeyNotFoundException;
use MauticPlugin\MauticCrmBundle\Api\Zoho\Mapper;

#[\PHPUnit\Framework\Attributes\CoversClass(Mapper::class)]
class MapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    protected $availableFields = [
        'Leads' => [
            'Company'   => [
                'type'     => 'string',
                'label'    => 'Company',
                'api_name' => 'Company',
                'required' => true,
            ],
            'FirstName' => [
                'type'     => 'string',
                'label'    => 'First Name',
                'api_name' => 'First Name',
                'required' => false,
            ],
            'LastName'  => [
                'type'     => 'string',
                'label'    => 'Last Name',
                'api_name' => 'Last Name',
                'required' => true,
            ],
            'Email'     => [
                'type'     => 'string',
                'label'    => 'Email',
                'api_name' => 'Email',
                'required' => false,
            ],
        ],
    ];

    /**
     * @var array
     */
    protected $mappedFields = [
        'Company'   => 'company',
        'Email'     => 'email',
        'Country'   => 'country',
        'FirstName' => 'firstname',
        'LastName'  => 'lastname',
    ];

    /**
     * @var array
     */
    protected $contacts = [
        [
            'firstname'             => 'FirstName1',
            'lastname'              => 'LastName1',
            'email'                 => 'zoho1@email.com',
            'integration_entity'    => 'Leads',
            'integration_entity_id' => 'abc',
            'internal_entity'       => 'lead',
            'internal_entity_id'    => 1,
        ],
        [
            'firstname'             => 'FirstName2',
            'lastname'              => 'LastName2',
            'email'                 => 'zoho2@email.com',
            'integration_entity'    => 'Leads',
            'integration_entity_id' => 'def',
            'internal_entity'       => 'lead',
            'internal_entity_id'    => 2,
        ],
        [
            'firstname'             => 'FirstName3',
            'lastname'              => 'LastName3',
            'email'                 => 'zoho3@email.com',
            'integration_entity'    => 'Leads',
            'integration_entity_id' => 'ghi',
            'internal_entity'       => 'lead',
            'internal_entity_id'    => 3,
        ],
    ];

    #[\PHPUnit\Framework\Attributes\TestDox('Test that array is generated according to the mapping')]
    public function testArrayIsGeneratedBasedOnMapping(): void
    {
        $mapper = new Mapper($this->availableFields);
        $mapper->setObject('Leads');

        foreach ($this->contacts as $contact) {
            $mapper->setMappedFields($this->mappedFields)
                ->setContact($contact)
                ->map($contact['internal_entity_id']);
        }

        $expected = [
            [
                'Email'      => 'zoho1@email.com',
                'First Name' => 'FirstName1',
                'Last Name'  => 'LastName1',
            ],
            [
                'Email'      => 'zoho2@email.com',
                'First Name' => 'FirstName2',
                'Last Name'  => 'LastName2',
            ],
            [
                'Email'      => 'zoho3@email.com',
                'First Name' => 'FirstName3',
                'Last Name'  => 'LastName3',
            ],
        ];

        $this->assertEquals($expected, $mapper->getArray());
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that contacts do not inherit previous contact information')]
    public function testContactDoesNotInheritPreviousContactData(): void
    {
        $mapper = new Mapper($this->availableFields);
        $mapper->setObject('Leads');

        $contacts                 = $this->contacts;
        $contacts[1]['firstname'] = null;

        foreach ($contacts as $contact) {
            $mapper->setMappedFields($this->mappedFields)
                ->setContact($contact)
                ->map($contact['internal_entity_id'], $contact['integration_entity_id']);
        }

        $expected = [
            [
                'id'         => 'abc',
                'Email'      => 'zoho1@email.com',
                'First Name' => 'FirstName1',
                'Last Name'  => 'LastName1',
            ],
            [
                'id'         => 'def',
                'Email'      => 'zoho2@email.com',
                'Last Name'  => 'LastName2',
            ],
            [
                'id'         => 'ghi',
                'Email'      => 'zoho3@email.com',
                'First Name' => 'FirstName3',
                'Last Name'  => 'LastName3',
            ],
        ];

        $this->assertEquals($expected, $mapper->getArray());
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that array is generated according to the mapping')]
    public function testArrayIsGeneratedBasedOnMappingWithId(): void
    {
        $mapper = new Mapper($this->availableFields);
        $mapper->setObject('Leads');

        foreach ($this->contacts as $contact) {
            $mapper->setMappedFields($this->mappedFields)
                ->setContact($contact)
                ->map($contact['internal_entity_id'], $contact['integration_entity_id']);
        }

        $expected = [
            [
                'id'         => 'abc',
                'Email'      => 'zoho1@email.com',
                'First Name' => 'FirstName1',
                'Last Name'  => 'LastName1',
            ],
            [
                'id'         => 'def',
                'First Name' => 'FirstName2',
                'Email'      => 'zoho2@email.com',
                'Last Name'  => 'LastName2',
            ],
            [
                'id'         => 'ghi',
                'Email'      => 'zoho3@email.com',
                'First Name' => 'FirstName3',
                'Last Name'  => 'LastName3',
            ],
        ];

        $this->assertEquals($expected, $mapper->getArray());
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test asking for a key returns the correct contact')]
    public function testThatContactIdMatchesGivenKey(): void
    {
        $mapper = new Mapper($this->availableFields);
        $mapper->setObject('Leads');

        foreach ($this->contacts as $contact) {
            $mapper->setMappedFields($this->mappedFields)
                ->setContact($contact)
                ->map($contact['internal_entity_id'], $contact['integration_entity_id']);
        }

        $this->assertEquals(3, $mapper->getContactIdByKey(2));
        $this->assertEquals(2, $mapper->getContactIdByKey(1));
        $this->assertEquals(1, $mapper->getContactIdByKey(0));
    }

    #[\PHPUnit\Framework\Attributes\TestDox("Test asking for a key that doesn't exist throws exception")]
    public function testThatExceptionIsThrownIfKeyNotFound(): void
    {
        $this->expectException(MatchingKeyNotFoundException::class);

        $mapper = new Mapper($this->availableFields);
        $mapper->setObject('Leads');

        foreach ($this->contacts as $contact) {
            $mapper->setMappedFields($this->mappedFields)
                ->setContact($contact)
                ->map($contact['internal_entity_id'], $contact['integration_entity_id']);
        }

        $mapper->getContactIdByKey(4);
    }
}
