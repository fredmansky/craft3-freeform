<?php
/**
 * Freeform for Craft
 *
 * @package       Solspace:Freeform
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2019, Solspace, Inc.
 * @link          https://docs.solspace.com/craft/freeform
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform;

use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\SiteEvent;
use craft\services\Dashboard;
use craft\services\Fields;
use craft\services\Sites;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use Solspace\Commons\Helpers\PermissionHelper;
use Solspace\Freeform\Controllers\ApiController;
use Solspace\Freeform\Controllers\CodepackController;
use Solspace\Freeform\Controllers\CrmController;
use Solspace\Freeform\Controllers\DashboardController;
use Solspace\Freeform\Controllers\FieldsController;
use Solspace\Freeform\Controllers\FormsController;
use Solspace\Freeform\Controllers\LogsController;
use Solspace\Freeform\Controllers\MailingListsController;
use Solspace\Freeform\Controllers\NotificationsController;
use Solspace\Freeform\Controllers\PaymentGatewaysController;
use Solspace\Freeform\Controllers\Pro\ExportProfilesController;
use Solspace\Freeform\Controllers\Pro\WebhooksController;
use Solspace\Freeform\Controllers\Pro\Payments\PaymentWebhooksController;
use Solspace\Freeform\Controllers\Pro\Payments\SubscriptionsController;
use Solspace\Freeform\Controllers\Pro\QuickExportController;
use Solspace\Freeform\Controllers\ResourcesController;
use Solspace\Freeform\Controllers\SettingsController;
use Solspace\Freeform\Controllers\SpamSubmissionsController;
use Solspace\Freeform\Controllers\StatusesController;
use Solspace\Freeform\Controllers\SubmissionsController;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Assets\RegisterEvent;
use Solspace\Freeform\Events\Freeform\RegisterCpSubnavItemsEvent;
use Solspace\Freeform\Events\Freeform\RegisterSettingsNavigationEvent;
use Solspace\Freeform\Events\Integrations\FetchCrmTypesEvent;
use Solspace\Freeform\Events\Integrations\FetchMailingListTypesEvent;
use Solspace\Freeform\Events\Integrations\FetchPaymentGatewayTypesEvent;
use Solspace\Freeform\Events\Integrations\FetchWebhookTypesEvent;
use Solspace\Freeform\FieldTypes\FormFieldType;
use Solspace\Freeform\FieldTypes\SubmissionFieldType;
use Solspace\Freeform\Library\Composer\Components\FieldInterface;
use Solspace\Freeform\Library\Pro\Payments\ElementHookHandlers\FormHookHandler;
use Solspace\Freeform\Library\Pro\Payments\ElementHookHandlers\SubmissionHookHandler;
use Solspace\Freeform\Models\FieldModel;
use Solspace\Freeform\Models\Settings;
use Solspace\Freeform\Records\StatusRecord;
use Solspace\Freeform\Resources\Bundles\Pro\Payments\PaymentsBundle;
use Solspace\Freeform\Services\ChartsService;
use Solspace\Freeform\Services\ConnectionsService;
use Solspace\Freeform\Services\CrmService;
use Solspace\Freeform\Services\DashboardService;
use Solspace\Freeform\Services\FieldsService;
use Solspace\Freeform\Services\FilesService;
use Solspace\Freeform\Services\FormsService;
use Solspace\Freeform\Services\HoneypotService;
use Solspace\Freeform\Services\IntegrationsQueueService;
use Solspace\Freeform\Services\IntegrationsService;
use Solspace\Freeform\Services\LoggerService;
use Solspace\Freeform\Services\MailerService;
use Solspace\Freeform\Services\MailingListsService;
use Solspace\Freeform\Services\NotificationsService;
use Solspace\Freeform\Services\PaymentGatewaysService;
use Solspace\Freeform\Services\Pro\ExportProfilesService;
use Solspace\Freeform\Services\Pro\Payments\PaymentNotificationsService;
use Solspace\Freeform\Services\Pro\Payments\PaymentsService;
use Solspace\Freeform\Services\Pro\Payments\StripeService;
use Solspace\Freeform\Services\Pro\Payments\SubscriptionPlansService;
use Solspace\Freeform\Services\Pro\Payments\SubscriptionsService;
use Solspace\Freeform\Services\Pro\ProFormsService;
use Solspace\Freeform\Services\Pro\RecaptchaService;
use Solspace\Freeform\Services\Pro\RulesService;
use Solspace\Freeform\Services\Pro\WebhooksService;
use Solspace\Freeform\Services\Pro\WidgetsService;
use Solspace\Freeform\Services\RelationsService;
use Solspace\Freeform\Services\SettingsService;
use Solspace\Freeform\Services\SpamSubmissionsService;
use Solspace\Freeform\Services\StatusesService;
use Solspace\Freeform\Services\SubmissionsService;
use Solspace\Freeform\Variables\FreeformPaymentsVariable;
use Solspace\Freeform\Variables\FreeformVariable;
use Solspace\Freeform\Widgets\ExtraWidgetInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use yii\base\Event;
use yii\db\Query;
use yii\web\ForbiddenHttpException;

/**
 * Class Plugin
 *
 * @property CrmService                  $crm
 * @property FieldsService               $fields
 * @property FilesService                $files
 * @property FormsService                $forms
 * @property MailerService               $mailer
 * @property MailingListsService         $mailingLists
 * @property NotificationsService        $notifications
 * @property SettingsService             $settings
 * @property StatusesService             $statuses
 * @property SubmissionsService          $submissions
 * @property SpamSubmissionsService      $spamSubmissions
 * @property LoggerService               $logger
 * @property HoneypotService             $honeypot
 * @property IntegrationsService         $integrations
 * @property IntegrationsQueueService    $integrationsQueue
 * @property PaymentGatewaysService      $paymentGateways
 * @property ConnectionsService          $connections
 * @property ChartsService               $charts
 * @property WidgetsService              $widgets
 * @property ExportProfilesService       $exportProfiles
 * @property RecaptchaService            $recaptcha
 * @property RulesService                $rules
 * @property ProFormsService             $proForms
 * @property PaymentNotificationsService $paymentNotifications
 * @property PaymentsService             $payments
 * @property StripeService               $stripe
 * @property SubscriptionPlansService    $subscriptionPlans
 * @property SubscriptionsService        $subscriptions
 * @property WebhooksService             $webhooks
 * @property RelationsService            $relations
 */
class Freeform extends Plugin
{
    const TRANSLATION_CATEGORY = 'freeform';

    const VIEW_DASHBOARD       = 'dashboard';
    const VIEW_FORMS           = 'forms';
    const VIEW_SUBMISSIONS     = 'submissions';
    const VIEW_FIELDS          = 'fields';
    const VIEW_NOTIFICATIONS   = 'notifications';
    const VIEW_SETTINGS        = 'settings';
    const VIEW_RESOURCES       = 'resources';
    const VIEW_EXPORT_PROFILES = 'export-profiles';

    const FIELD_DISPLAY_ORDER_TYPE = 'type';
    const FIELD_DISPLAY_ORDER_NAME = 'name';

    const EDITION_LITE = 'lite';
    const EDITION_PRO  = 'pro';

    const PERMISSIONS_HELP_LINK = 'https://docs.solspace.com/craft/freeform/v2/setup/demo-templates.html';
    const PERMISSION_NAMESPACE  = 'Freeform';

    const VERSION_CACHE_KEY           = 'freeform_version';
    const VERSION_CACHE_TIMESTAMP_KEY = 'freeform_version_timestamp';
    const VERSION_CACHE_TTL           = 86400; // 24-hours

    const PERMISSION_FORMS_ACCESS           = 'freeform-formsAccess';
    const PERMISSION_FORMS_MANAGE           = 'freeform-formsManage';
    const PERMISSION_FIELDS_ACCESS          = 'freeform-fieldsAccess';
    const PERMISSION_FIELDS_MANAGE          = 'freeform-fieldsManage';
    const PERMISSION_SETTINGS_ACCESS        = 'freeform-settingsAccess';
    const PERMISSION_SUBMISSIONS_ACCESS     = 'freeform-submissionsAccess';
    const PERMISSION_SUBMISSIONS_MANAGE     = 'freeform-submissionsManage';
    const PERMISSION_NOTIFICATIONS_ACCESS   = 'freeform-notificationsAccess';
    const PERMISSION_NOTIFICATIONS_MANAGE   = 'freeform-notificationsManage';
    const PERMISSION_DASHBOARD_ACCESS       = 'freeform-dashboardAccess';
    const PERMISSION_ERROR_LOG_ACCESS       = 'freeform-errorLogAccess';
    const PERMISSION_ERROR_LOG_MANAGE       = 'freeform-errorLogManage';
    const PERMISSION_RESOURCES              = 'freeform-resources';
    const PERMISSION_EXPORT_PROFILES_ACCESS = 'freeform-pro-exportProfilesAccess';
    const PERMISSION_EXPORT_PROFILES_MANAGE = 'freeform-pro-exportProfilesManage';

    const EVENT_REGISTER_SUBNAV_ITEMS = 'registerSubnavItems';

    /** @var bool */
    public $hasCpSettings = true;

    /**
     * @return Plugin|Freeform
     */
    public static function getInstance(): Freeform
    {
        return parent::getInstance();
    }

    /**
     * @inheritDoc
     */
    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PRO,
        ];
    }

    /**
     * @param string $message
     * @param array  $params
     * @param string $language
     *
     * @return string
     */
    public static function t(string $message, array $params = [], string $language = null): string
    {
        return \Craft::t(self::TRANSLATION_CATEGORY, $message, $params, $language);
    }

    /**
     * @return bool
     */
    public function isPro(): bool
    {
        return $this->edition === self::EDITION_PRO;
    }

    /**
     * @return bool
     */
    public function isLite(): bool
    {
        return $this->edition === self::EDITION_LITE;
    }

    /**
     * @throws ForbiddenHttpException
     */
    public function requirePro()
    {
        if (!$this->isPro()) {
            throw new ForbiddenHttpException(self::t('Requires Freeform Pro'));
        }
    }

    /**
     * Includes CSS and JS files
     * Registers custom class auto-loader
     */
    public function init()
    {
        parent::init();
        \Yii::setAlias('@freeform', __DIR__);

        $this->initControllerMap();
        $this->initServices();
        $this->initRoutes();
        $this->initIntegrations();
        $this->initTwigVariables();
        $this->initWidgets();
        $this->initFieldTypes();
        $this->initPermissions();
        $this->initEventListeners();
        $this->initHoneypot();
        $this->initConnections();
        $this->initSpamCheck();
        $this->initPaymentAssets();
        $this->initHookHandlers();

        if ($this->isPro() && $this->settings->getPluginName()) {
            $this->name = $this->settings->getPluginName();
        } else {
            $this->name = 'Freeform';
        }

        if ($this->isInstalled) {
            // Perform unfinalized asset cleanup
            $this->files->cleanUpUnfinalizedAssets();
            $this->submissions->purgeSubmissions();
            $this->spamSubmissions->purgeSubmissions();
        }
    }

    /**
     * @return array|null
     */
    public function getCpNavItem()
    {
        $navItem = parent::getCpNavItem();

        $subNavigation = include __DIR__ . '/subnav.php';
        $event         = new RegisterCpSubnavItemsEvent($subNavigation);
        $this->trigger(self::EVENT_REGISTER_SUBNAV_ITEMS, $event);

        $navItem['subnav'] = $event->getSubnavItems();

        return $navItem;
    }

    /**
     * @return Settings
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * @return string
     */
    protected function settingsHtml(): string
    {
        return \Craft::$app->getView()->renderTemplate(
            'freeform/settings',
            [
                'settings' => $this->getSettings(),
            ]
        );
    }


    /**
     * On install - insert default statuses
     *
     * @return void
     */
    public function afterInstall()
    {
        $fieldService = self::getInstance()->fields;

        $field         = FieldModel::create();
        $field->handle = 'firstName';
        $field->label  = 'First Name';
        $field->type   = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field         = FieldModel::create();
        $field->handle = 'lastName';
        $field->label  = 'Last Name';
        $field->type   = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field         = FieldModel::create();
        $field->handle = 'email';
        $field->label  = 'Email';
        $field->type   = FieldInterface::TYPE_EMAIL;
        $fieldService->save($field);

        $field         = FieldModel::create();
        $field->handle = 'website';
        $field->label  = 'Website';
        $field->type   = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field         = FieldModel::create();
        $field->handle = 'cellPhone';
        $field->label  = 'Cell Phone';
        $field->type   = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field         = FieldModel::create();
        $field->handle = 'homePhone';
        $field->label  = 'Home Phone';
        $field->type   = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field         = FieldModel::create();
        $field->handle = 'companyName';
        $field->label  = 'Company Name';
        $field->type   = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field         = FieldModel::create();
        $field->handle = 'address';
        $field->label  = 'Address';
        $field->setMetaProperty('rows', 2);
        $field->type = FieldInterface::TYPE_TEXTAREA;
        $fieldService->save($field);

        $field         = FieldModel::create();
        $field->handle = 'city';
        $field->label  = 'City';
        $field->type   = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field         = FieldModel::create();
        $field->handle = 'state';
        $field->label  = 'State';
        $field->type   = FieldInterface::TYPE_SELECT;
        $field->setMetaProperty('options', include __DIR__ . '/Resources/states.php');
        $fieldService->save($field);

        $field         = FieldModel::create();
        $field->handle = 'zipCode';
        $field->label  = 'Zip Code';
        $field->type   = FieldInterface::TYPE_TEXT;
        $fieldService->save($field);

        $field         = FieldModel::create();
        $field->handle = 'message';
        $field->label  = 'Message';
        $field->type   = FieldInterface::TYPE_TEXTAREA;
        $field->setMetaProperty('rows', 5);
        $fieldService->save($field);

        $field         = FieldModel::create();
        $field->handle = 'number';
        $field->label  = 'Number';
        $field->type   = FieldInterface::TYPE_NUMBER;
        $fieldService->save($field);

        $status            = StatusRecord::create();
        $status->name      = 'Pending';
        $status->handle    = 'pending';
        $status->color     = 'light';
        $status->sortOrder = 1;
        $status->save();

        $status            = StatusRecord::create();
        $status->name      = 'Open';
        $status->handle    = 'open';
        $status->color     = 'green';
        $status->sortOrder = 2;
        $status->isDefault = 1;
        $status->save();

        $status            = StatusRecord::create();
        $status->name      = 'Closed';
        $status->handle    = 'closed';
        $status->color     = 'grey';
        $status->sortOrder = 3;
        $status->save();

        $field         = FieldModel::create();
        $field->handle = 'payment';
        $field->label  = '';
        $field->type   = FieldInterface::TYPE_CREDIT_CARD_DETAILS;
        $fieldService->save($field);
    }

    private function initControllerMap()
    {
        if (!\Craft::$app->request->isConsoleRequest) {
            $this->controllerMap = [
                'dashboard'        => DashboardController::class,
                'api'              => ApiController::class,
                'codepack'         => CodepackController::class,
                'crm'              => CrmController::class,
                'mailing-lists'    => MailingListsController::class,
                'payment-gateways' => PaymentGatewaysController::class,
                'fields'           => FieldsController::class,
                'forms'            => FormsController::class,
                'logs'             => LogsController::class,
                'notifications'    => NotificationsController::class,
                'submissions'      => SubmissionsController::class,
                'spam-submissions' => SpamSubmissionsController::class,
                'statuses'         => StatusesController::class,
                'settings'         => SettingsController::class,
                'resources'        => ResourcesController::class,
                'quick-export'     => QuickExportController::class,
                'export-profiles'  => ExportProfilesController::class,
                'subscriptions'    => SubscriptionsController::class,
                'payment-webhooks' => PaymentWebhooksController::class,
                'webhooks'         => WebhooksController::class,
            ];
        }
    }

    private function initServices()
    {
        $this->setComponents(
            [
                'dashboard'            => DashboardService::class,
                'crm'                  => CrmService::class,
                'charts'               => ChartsService::class,
                'fields'               => FieldsService::class,
                'files'                => FilesService::class,
                'forms'                => FormsService::class,
                'mailer'               => MailerService::class,
                'mailingLists'         => MailingListsService::class,
                'notifications'        => NotificationsService::class,
                'settings'             => SettingsService::class,
                'statuses'             => StatusesService::class,
                'submissions'          => SubmissionsService::class,
                'spamSubmissions'      => SpamSubmissionsService::class,
                'logger'               => LoggerService::class,
                'honeypot'             => HoneypotService::class,
                'integrations'         => IntegrationsService::class,
                'integrationsQueue'    => IntegrationsQueueService::class,
                'paymentGateways'      => PaymentGatewaysService::class,
                'connections'          => ConnectionsService::class,
                'widgets'              => WidgetsService::class,
                'exportProfiles'       => ExportProfilesService::class,
                'recaptcha'            => RecaptchaService::class,
                'rules'                => RulesService::class,
                'proForms'             => ProFormsService::class,
                'paymentNotifications' => PaymentNotificationsService::class,
                'payments'             => PaymentsService::class,
                'stripe'               => StripeService::class,
                'subscriptionPlans'    => SubscriptionPlansService::class,
                'subscriptions'        => SubscriptionsService::class,
                'webhooks'             => WebhooksService::class,
                'relations'            => RelationsService::class,
            ]
        );
    }

    private function initIntegrations()
    {
        Event::on(
            CrmService::class,
            CrmService::EVENT_FETCH_TYPES,
            function (FetchCrmTypesEvent $event) {
                $finder = new Finder();

                $namespace = 'Solspace\Freeform\Integrations\CRM';

                /** @var SplFileInfo[] $files */
                $files = $finder
                    ->name('*.php')
                    ->files()
                    ->ignoreDotFiles(true)
                    ->in(__DIR__ . '/Integrations/CRM/');

                foreach ($files as $file) {
                    $className = str_replace('.' . $file->getExtension(), '', $file->getBasename());
                    $className = $namespace . '\\' . $className;
                    $event->addType($className);
                }
            }
        );

        Event::on(
            MailingListsService::class,
            MailingListsService::EVENT_FETCH_TYPES,
            function (FetchMailingListTypesEvent $event) {
                $finder = new Finder();

                $namespace = 'Solspace\Freeform\Integrations\MailingLists';

                /** @var SplFileInfo[] $files */
                $files = $finder
                    ->name('*.php')
                    ->files()
                    ->ignoreDotFiles(true)
                    ->in(__DIR__ . '/Integrations/MailingLists/');

                foreach ($files as $file) {
                    $className = str_replace('.' . $file->getExtension(), '', $file->getBasename());
                    $className = $namespace . '\\' . $className;
                    $event->addType($className);
                }
            }
        );

        Event::on(
            PaymentGatewaysService::class,
            PaymentGatewaysService::EVENT_FETCH_TYPES,
            function (FetchPaymentGatewayTypesEvent $event) {
                $finder = new Finder();

                $namespace = 'Solspace\Freeform\Integrations\PaymentGateways';

                /** @var SplFileInfo[] $files */
                $files = $finder
                    ->name('*.php')
                    ->files()
                    ->ignoreDotFiles(true)
                    ->in(__DIR__ . '/Integrations/PaymentGateways/');

                foreach ($files as $file) {
                    $className = str_replace('.' . $file->getExtension(), '', $file->getBasename());
                    $className = $namespace . '\\' . $className;
                    $event->addType($className);
                }
            }
        );

        Event::on(
            WebhooksService::class,
            WebhooksService::EVENT_FETCH_TYPES,
            function (FetchWebhookTypesEvent $event) {
                $finder = new Finder();

                $namespace = 'Solspace\Freeform\Webhooks\Integrations';

                /** @var SplFileInfo[] $files */
                $files = $finder
                    ->name('*.php')
                    ->files()
                    ->ignoreDotFiles(true)
                    ->in(__DIR__ . '/Webhooks/Integrations/');

                foreach ($files as $file) {
                    $className = str_replace('.' . $file->getExtension(), '', $file->getBasename());
                    $className = $namespace . '\\' . $className;
                    $event->addType($className);
                }
            }
        );
    }

    private function initRoutes()
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $routes       = include __DIR__ . '/routes.php';
                $event->rules = array_merge($event->rules, $routes);
            }
        );
    }

    private function initTwigVariables()
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $event->sender->set('freeform', FreeformVariable::class);
                $event->sender->set('freeformPayments', FreeformPaymentsVariable::class);
            }
        );
    }

    private function initWidgets()
    {
        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $finder = new Finder();

                $namespace = 'Solspace\Freeform\Widgets';

                /** @var SplFileInfo[] $files */
                $files = $finder
                    ->name('*Widget.php')
                    ->files()
                    ->ignoreDotFiles(true)
                    ->notName('Abstract*.php')
                    ->in(__DIR__ . '/Widgets/');

                foreach ($files as $file) {
                    $isForPro = $file->getRelativePath() === 'Pro';

                    $className = str_replace('.' . $file->getExtension(), '', $file->getBasename());
                    $className = $namespace . ($isForPro ? '\\Pro' : '') . '\\' . $className;

                    $reflectionClass = new \ReflectionClass($className);
                    if (!$this->isPro() && $reflectionClass->implementsInterface(ExtraWidgetInterface::class)) {
                        continue;
                    }

                    $event->types[] = $className;
                }
            }
        );
    }

    private function initFieldTypes()
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = FormFieldType::class;
                $event->types[] = SubmissionFieldType::class;
            }
        );
    }

    private function initPermissions()
    {
        if (\Craft::$app->getEdition() >= \Craft::Pro) {
            Event::on(
                UserPermissions::class,
                UserPermissions::EVENT_REGISTER_PERMISSIONS,
                function (RegisterUserPermissionsEvent $event) {
                    $forms = $this->forms->getAllForms();

                    $submissionNestedPermissions = [
                        self::PERMISSION_SUBMISSIONS_MANAGE => [
                            'label' => self::t(
                                'Manage All Submissions'
                            ),
                        ],
                    ];

                    foreach ($forms as $form) {
                        $permissionName = PermissionHelper::prepareNestedPermission(
                            self::PERMISSION_SUBMISSIONS_MANAGE,
                            $form->id
                        );

                        $submissionNestedPermissions[$permissionName] = ['label' => 'For ' . $form->name];
                    }

                    $permissions = [
                        self::PERMISSION_DASHBOARD_ACCESS       => ['label' => self::t('Access Dashboard')],
                        self::PERMISSION_SUBMISSIONS_ACCESS     => [
                            'label'  => self::t('Access Submissions'),
                            'nested' => $submissionNestedPermissions,
                        ],
                        self::PERMISSION_FORMS_ACCESS           => [
                            'label'  => self::t('Access Forms'),
                            'nested' => [
                                self::PERMISSION_FORMS_MANAGE => ['label' => self::t('Manage Forms')],
                            ],
                        ],
                        self::PERMISSION_FIELDS_ACCESS          => [
                            'label'  => self::t('Access Fields'),
                            'nested' => [
                                self::PERMISSION_FIELDS_MANAGE => ['label' => self::t('Manage Fields')],
                            ],
                        ],
                        self::PERMISSION_NOTIFICATIONS_ACCESS   => [
                            'label'  => self::t('Access Email Templates'),
                            'nested' => [
                                self::PERMISSION_NOTIFICATIONS_MANAGE => [
                                    'label' => self::t(
                                        'Manage Email Templates'
                                    ),
                                ],
                            ],
                        ],
                        self::PERMISSION_SETTINGS_ACCESS        => ['label' => self::t('Access Settings')],
                        self::PERMISSION_RESOURCES              => ['label' => self::t('Access Resources')],
                        self::PERMISSION_EXPORT_PROFILES_ACCESS => [
                            'label'  => self::t('Access Export Profiles'),
                            'nested' => [
                                self::PERMISSION_EXPORT_PROFILES_MANAGE => [
                                    'label' => self::t(
                                        'Manage Export Profiles'
                                    ),
                                ],
                            ],
                        ],
                    ];

                    if (!isset($event->permissions[self::PERMISSION_NAMESPACE])) {
                        $event->permissions[self::PERMISSION_NAMESPACE] = [];
                    }

                    $event->permissions[self::PERMISSION_NAMESPACE] = array_merge(
                        $event->permissions[self::PERMISSION_NAMESPACE],
                        $permissions
                    );
                }
            );
        }
    }

    private function initEventListeners()
    {
        Event::on(
            FormsService::class,
            FormsService::EVENT_RENDER_CLOSING_TAG,
            [$this->forms, 'addFormPluginJavascript']
        );

        Event::on(
            Sites::class,
            Sites::EVENT_BEFORE_SAVE_SITE,
            function (SiteEvent $event) {
                if ($event->site->primary && (int) $event->site->id !== (int) $event->oldPrimarySiteId) {
                    $oldId = $event->oldPrimarySiteId;
                    $newId = $event->site->id;

                    $ids = (new Query())
                        ->select('[[id]]')
                        ->from('{{%elements}}')
                        ->where(['[[type]]' => Submission::class])
                        ->column();

                    \Craft::$app
                        ->db
                        ->createCommand()
                        ->update(
                            '{{%elements_sites}}',
                            ['siteId' => $newId],
                            ['siteId' => $oldId, 'elementId' => $ids]
                        )
                        ->execute();

                    \Craft::$app
                        ->db
                        ->createCommand()
                        ->update(
                            '{{%content}}',
                            ['siteId' => $newId],
                            ['siteId' => $oldId, 'elementId' => $ids]
                        )
                        ->execute();
                }
            }
        );

        Event::on(
            FieldsService::class,
            FieldsService::EVENT_AFTER_VALIDATE,
            [$this->recaptcha, 'validateRecaptchaV2Checkbox']
        );

        Event::on(
            FormsService::class,
            FormsService::EVENT_FORM_VALIDATE,
            [$this->recaptcha, 'validateRecaptchaV2Invisible']
        );

        Event::on(
            FormsService::class,
            FormsService::EVENT_FORM_VALIDATE,
            [$this->recaptcha, 'validateRecaptchaV3']
        );

        Event::on(
            SettingsService::class,
            SettingsService::EVENT_REGISTER_SETTINGS_NAVIGATION,
            function (RegisterSettingsNavigationEvent $event) {
                if ($this->settings->isAllowAdminEdit()) {
                    $event->addNavigationItem('recaptcha', Freeform::t('reCAPTCHA'), 'spam');
                }
            }
        );

        Event::on(
            FormsService::class,
            FormsService::EVENT_RENDER_CLOSING_TAG,
            [$this->recaptcha, 'addRecaptchaJavascriptToForm']
        );

        Event::on(
            FormsService::class,
            FormsService::EVENT_AFTER_SUBMIT,
            [$this->relations, 'relate']
        );

        if ($this->isPro()) {
            Event::on(
                FormsService::class,
                FormsService::EVENT_FORM_VALIDATE,
                [$this->forms, 'checkReachedPostingLimit']
            );

            Event::on(
                FormsService::class,
                FormsService::EVENT_RENDER_CLOSING_TAG,
                [$this->proForms, 'addDateTimeJavascript']
            );

            Event::on(
                FormsService::class,
                FormsService::EVENT_RENDER_CLOSING_TAG,
                [$this->proForms, 'addPhonePatternJavascript']
            );

            Event::on(
                FormsService::class,
                FormsService::EVENT_RENDER_CLOSING_TAG,
                [$this->proForms, 'addOpinionScaleStyles']
            );

            Event::on(
                FormsService::class,
                FormsService::EVENT_RENDER_CLOSING_TAG,
                [$this->rules, 'addJavascriptToForm']
            );

            Event::on(
                FormsService::class,
                FormsService::EVENT_ATTACH_FORM_ATTRIBUTES,
                [$this->rules, 'addAttributesToFormTag']
            );

            Event::on(
                FormsService::class,
                FormsService::EVENT_PAGE_JUMP,
                [$this->rules, 'handleFormPageJump']
            );

            Event::on(
                SubmissionsController::class,
                SubmissionsController::EVENT_REGISTER_EDIT_ASSETS,
                [$this->rules, 'registerRulesJsAsAssets']
            );

            Event::on(
                FormsService::class,
                FormsService::EVENT_RENDER_CLOSING_TAG,
                [$this->stripe, 'addFormJavascript']
            );

            Event::on(
                FormsService::class,
                FormsService::EVENT_AFTER_SUBMIT,
                [$this->webhooks, 'triggerWebhooks']
            );
        }
    }

    private function initHoneypot()
    {
        if ($this->settings->isFreeformHoneypotEnabled()) {
            Event::on(
                FormsService::class,
                FormsService::EVENT_RENDER_OPENING_TAG,
                [$this->honeypot, 'addHoneyPotInputToForm']
            );

            Event::on(
                FormsService::class,
                FormsService::EVENT_RENDER_CLOSING_TAG,
                [$this->honeypot, 'addFormJavascript']
            );

            Event::on(
                FormsService::class,
                FormsService::EVENT_FORM_VALIDATE,
                [$this->honeypot, 'validateFormHoneypot']
            );
        }
    }

    private function initConnections()
    {
        Event::on(
            FormsService::class,
            FormsService::EVENT_FORM_VALIDATE,
            [$this->connections, 'validateConnections']
        );
    }

    private function initSpamCheck()
    {
        Event::on(
            FormsService::class,
            FormsService::EVENT_FORM_VALIDATE,
            [$this->settings, 'checkSubmissionForSpam']
        );

        Event::on(
            FormsService::class,
            FormsService::EVENT_FORM_VALIDATE,
            [$this->settings, 'checkBlacklistedIps']
        );

        Event::on(
            FormsService::class,
            FormsService::EVENT_FORM_VALIDATE,
            [$this->settings, 'throttleSubmissions']
        );
    }

    private function initPaymentAssets()
    {
        if (!$this->isPro()) {
            return;
        }

        Event::on(
            SubmissionsController::class,
            SubmissionsController::EVENT_REGISTER_INDEX_ASSETS,
            function (RegisterEvent $event) {
                $event->getView()->registerAssetBundle(PaymentsBundle::class);
            }
        );

        Event::on(
            SubmissionsController::class,
            SubmissionsController::EVENT_REGISTER_EDIT_ASSETS,
            function (RegisterEvent $event) {
                $event->getView()->registerAssetBundle(PaymentsBundle::class);
            }
        );
    }

    private function initHookHandlers()
    {
        if (!$this->isPro()) {
            return;
        }

        SubmissionHookHandler::registerHooks();
        FormHookHandler::registerHooks();
    }
}
