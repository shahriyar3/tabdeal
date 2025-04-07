# Backwards compatibility breaking changes

## Platform requirements
- The minimum required PHP version has been increased from **8.1** to **8.2**.

## Removed features
- The ability to update Mautic in the browser (via user interface) has been removed. To update Mautic, use the **command line** instead.

## BC breaks in the code

### PHP
- Removed `\Mautic\DashboardBundle\Dashboard\Widget::FORMAT_MYSQL` constant. Use `DateTimeHelper::FORMAT_DB_DATE_ONLY` instead.
