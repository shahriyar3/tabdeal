<?php

namespace Mautic\ConfigBundle\Tests\Mapper;

use Mautic\ConfigBundle\Exception\BadFormConfigException;
use Mautic\ConfigBundle\Mapper\ConfigMapper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

#[\PHPUnit\Framework\Attributes\CoversClass(BadFormConfigException::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(ConfigMapper::class)]
class ConfigMapperTest extends \PHPUnit\Framework\TestCase
{
    private $forms = [
        'emailconfig' => [
            'bundle'     => 'EmailBundle',
            'formAlias'  => 'emailconfig',
            'formTheme'  => 'MauticEmailBundle:FormTheme\\Config',
            'parameters' => [
                'mailer_from_name'                      => 'Mautic',
                'mailer_from_email'                     => 'email@yoursite.com',
                'mailer_return_path'                    => null,
                'mailer_transport'                      => 'mail',
                'mailer_append_tracking_pixel'          => true,
                'mailer_convert_embed_images'           => false,
                'mailer_dsn'                            => 'smtp://null:25',
                'messenger_dsn_email'                   => 'doctrine://default',
                'messenger_retry_strategy_max_retries'  => 3,
                'messenger_retry_strategy_delay'        => 1000,
                'messenger_retry_strategy_multiplier'   => 2,
                'messenger_retry_strategy_max_delay'    => 0,
                'unsubscribe_text'                      => null,
                'webview_text'                          => null,
                'unsubscribe_message'                   => null,
                'resubscribe_message'                   => null,
                'monitored_email'                       => [
                    'general' => [
                        'address'    => null,
                        'host'       => null,
                        'port'       => '993',
                        'encryption' => '/ssl',
                        'user'       => null,
                        'password'   => null,
                    ],
                    'EmailBundle_bounces' => [
                        'address'           => null,
                        'host'              => null,
                        'port'              => '993',
                        'encryption'        => '/ssl',
                        'user'              => null,
                        'password'          => null,
                        'override_settings' => 0,
                        'folder'            => null,
                    ],
                    'EmailBundle_unsubscribes' => [
                        'address'           => null,
                        'host'              => null,
                        'port'              => '993',
                        'encryption'        => '/ssl',
                        'user'              => null,
                        'password'          => null,
                        'override_settings' => 0,
                        'folder'            => null,
                    ],
                    'EmailBundle_replies' => [
                        'address'           => null,
                        'host'              => null,
                        'port'              => '993',
                        'encryption'        => '/ssl',
                        'user'              => null,
                        'password'          => null,
                        'override_settings' => 0,
                        'folder'            => null,
                    ],
                ],
                'mailer_is_owner'                     => false,
                'default_signature_text'              => null,
                'email_frequency_number'              => null,
                'email_frequency_time'                => null,
                'show_contact_preferences'            => false,
                'show_contact_frequency'              => false,
                'show_contact_pause_dates'            => false,
                'show_contact_preferred_channels'     => false,
                'show_contact_categories'             => false,
                'show_contact_segments'               => false,
                'mailer_mailjet_sandbox'              => false,
                'mailer_mailjet_sandbox_default_mail' => null,
                'disable_trackable_urls'              => false,
            ],
        ],
    ];

    private $config = [
        'db_host'         => 'dbhost',
        'db_user'         => 'dbuser',
        'monitored_email' => [
            'general' => [
                'address'    => 'test@test.com',
                'host'       => 'test.com',
                'port'       => '143',
                'encryption' => '/tls/novalidate-cert',
                'user'       => 'test@test.com',
                'password'   => 'password',
            ],
            'EmailBundle_bounces' => [
                'address'           => 'test2@test.com',
                'host'              => 'test2.com',
                'port'              => '143',
                'encryption'        => '/tls/novalidate-cert',
                'user'              => 'test2@test.com',
                'password'          => 'password',
                'override_settings' => 1,
                'folder'            => 'INBOX',
            ],
            'EmailBundle_unsubscribes' => [
                'address'           => 'test3@test.com',
                'host'              => null,
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => null,
                'password'          => null,
                'override_settings' => 0,
                'folder'            => 'INBOX',
            ],
            'EmailBundle_replies' => [
                'address'           => 'test4@test.com',
                'host'              => null,
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => null,
                'password'          => null,
                'override_settings' => 0,
                'folder'            => 'INBOX',
            ],
        ],
    ];

    #[\PHPUnit\Framework\Attributes\TestDox('Exception should be thrown if parameters key is not found in a form config')]
    public function testExceptionIsThrownOnBadFormConfig(): void
    {
        $this->expectException(BadFormConfigException::class);

        $forms = [
            'emailconfig' => [
                'bundle'    => 'EmailBundle',
                'formAlias' => 'emailconfig',
                'formTheme' => 'MauticEmailBundle:FormTheme\Config',
            ],
        ];

        $parameterHelper = $this->createMock(CoreParametersHelper::class);

        $mapper = new ConfigMapper($parameterHelper, []);

        $mapper->bindFormConfigsWithRealValues($forms);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Defaults should be bound when local config has no values')]
    public function testParametersAreBoundToDefaults(): void
    {
        $parameterHelper = $this->createMock(CoreParametersHelper::class);

        $mapper = new ConfigMapper($parameterHelper, []);

        $processedForms = $mapper->bindFormConfigsWithRealValues($this->forms);

        $this->assertEquals($this->forms, $processedForms);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Defaults should be merged with local config values')]
    public function testParametersAreBoundToDefaultsWithLocalConfig(): void
    {
        $parameterHelper = $this->createMock(CoreParametersHelper::class);

        $parameterHelper->method('get')
            ->willReturnCallback(
                fn ($param, $defaultValue) => array_key_exists($param, $this->config) ? $this->config[$param] : $defaultValue
            );

        $mapper = new ConfigMapper($parameterHelper, []);

        $forms          = $this->forms;
        $processedForms = $mapper->bindFormConfigsWithRealValues($forms);

        // Update expected
        $forms['emailconfig']['parameters']['monitored_email'] = [
            'general' => [
                'address'    => 'test@test.com',
                'host'       => 'test.com',
                'port'       => '143',
                'encryption' => '/tls/novalidate-cert',
                'user'       => 'test@test.com',
                'password'   => 'password',
            ],
            'EmailBundle_bounces' => [
                'address'           => 'test2@test.com',
                'host'              => 'test2.com',
                'port'              => '143',
                'encryption'        => '/tls/novalidate-cert',
                'user'              => 'test2@test.com',
                'password'          => 'password',
                'override_settings' => 1,
                'folder'            => 'INBOX',
            ],
            'EmailBundle_unsubscribes' => [
                'address'           => 'test3@test.com',
                'host'              => null,
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => null,
                'password'          => null,
                'override_settings' => 0,
                'folder'            => 'INBOX',
            ],
            'EmailBundle_replies' => [
                'address'           => 'test4@test.com',
                'host'              => null,
                'port'              => '993',
                'encryption'        => '/ssl',
                'user'              => null,
                'password'          => null,
                'override_settings' => 0,
                'folder'            => 'INBOX',
            ],
        ];

        $this->assertEquals($forms, $processedForms);
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Defaults should be merged with local config values but restricted fields should be removed')]
    public function testParametersAreBoundToDefaultsWithLocalConfigAndRestrictionsAppied(): void
    {
        $parameterHelper = $this->createMock(CoreParametersHelper::class);

        $parameterHelper->method('get')
            ->willReturnCallback(
                fn ($param, $defaultValue) => array_key_exists($param, $this->config) ? $this->config[$param] : $defaultValue
            );

        $mapper = new ConfigMapper($parameterHelper, ['monitored_email']);

        $forms          = $this->forms;
        $processedForms = $mapper->bindFormConfigsWithRealValues($forms);

        // Expected should have had monitored_email unset due to it being restricted
        unset($forms['emailconfig']['parameters']['monitored_email']);

        $this->assertEquals($forms, $processedForms);
    }
}
