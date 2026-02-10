<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php', require __DIR__ . '/../../common/config/params-local.php', require __DIR__ . '/params.php', require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'name' => 'easy installment manager',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'aliases' => [
        '@mdm/admin' => '@vendor/mdmsoft/yii2-admin',
    ],
    'modules' => [
        'LawyersImage' => [
            'class' => 'backend\modules\LawyersImage\LawyersImage',
        ],
        'address' => [
            'class' => 'backend\modules\address\Address',
        ],
        'divisionsCollection' => [
            'class' => 'backend\modules\divisionsCollection\DivisionsCollection',
        ],
        'sms' => [
            'class' => 'backend\modules\sms\Sms',
        ],
        'contracts' => [
            'class' => 'backend\modules\contracts\Contracts',
        ],
        'companyBanks' => [
            'class' => 'backend\modules\companyBanks\CompanyBanks',
        ],
        'status' => [
            'class' => 'backend\modules\status\Status',
        ],
        'rejesterFollowUpType' => [
            'class' => 'backend\modules\rejesterFollowUpType\RejesterFollowUpType',
        ],
        'shareholders' => [
            'class' => 'backend\modules\shareholders\Shareholders',
        ],
        'workdays' => [
            'class' => 'backend\modules\workdays\Workdays',
        ],

        'shares' => [
            'class' => 'backend\modules\shares\Shares',
        ],
        'phoneNumbers' => [
            'class' => 'backend\modules\phoneNumbers\PhoneNumbers',
        ],
        'notification' => [
            'class' => 'backend\modules\notification\Notification',
        ],
        'cousins' => [
            'class' => 'backend\modules\cousins\Cousins',
        ],
        'citizen' => [
            'class' => 'backend\modules\citizen\Citizen',
        ],
        'movment' => [
            'class' => 'backend\modules\movment\Movment',
        ],
        'location' => [
            'class' => 'backend\modules\location\Location',
        ],
        'loanScheduling' => [
            'class' => 'backend\modules\loanScheduling\LoanScheduling',
        ],
        'leaveRequest' => [
            'class' => 'backend\modules\leaveRequest\LeaveRequest',
        ],
        'leaveTypes' => [
            'class' => 'backend\modules\leaveTypes\LeaveTypes',
        ],
        'lawyers' => [
            'class' => 'backend\modules\lawyers\Lawyers',
        ],
        'inventoryItemQuantities' => [
            'class' => 'backend\modules\inventoryItemQuantities\InventoryItemQuantities',
        ],
        'inventoryItems' => [
            'class' => 'backend\modules\inventoryItems\InventoryItems',
        ],
        'items' => [
            'class' => 'backend\modules\items\Items',
        ],
        'jobs' => [
            'class' => 'backend\modules\jobs\Jobs',
        ],
        'judiciary' => [
            'class' => 'backend\modules\judiciary\Judiciary',
        ],

        'inventoryStockLocations' => [
            'class' => 'backend\modules\inventoryStockLocations\InventoryStockLocations',
        ],
        'judiciaryType' => [
            'class' => 'backend\modules\judiciaryType\JudiciaryType',
        ],
        'realEstate' => [
            'class' => 'backend\modules\realEstate\RealEstate',
        ],
        'inventorySuppliers' => [
            'class' => 'backend\modules\inventorySuppliers\InventorySuppliers',
        ],
        'invoice' => [
            'class' => 'backend\modules\invoice\Invoice',
        ],
        'holidays' => [
            'class' => 'backend\modules\holidays\Holidays',
        ],
        'imagemanager' => [
            'class' => 'backend\modules\imagemanager\Imagemanager',
        ],
        'income' => [
            'class' => 'backend\modules\income\Income',
        ],
        'incomeCategory' => [
            'class' => 'backend\modules\incomeCategory\IncomeCategory',
        ],
        'leavePolicy' => [
            'class' => 'backend\modules\leavePolicy\LeavePolicy',
        ],
        'department' => [
            'class' => 'backend\modules\department\Department',
        ],
        'bancks' => [
            'class' => 'backend\modules\bancks\Bancks',
        ],
        'documentStatus' => [
            'class' => 'backend\modules\documentStatus\DocumentStatus',
        ],
        'hearAboutUs' => [
            'class' => 'backend\modules\hearAboutUs\HearAboutUs',
        ],
        'city' => [
            'class' => 'backend\modules\city\City',
        ],
        'paymentType' => [
            'class' => 'backend\modules\paymentType\PaymentType',
        ],
        'feelings' => [
            'class' => 'backend\modules\feelings\Feelings',
        ],
        'contactType' => [
            'class' => 'backend\modules\contactType\ContactType',
        ],
        'connectionResponse' => [
            'class' => 'backend\modules\connectionResponse\ConnectionResponse',
        ],
        'documentType' => [
            'class' => 'backend\modules\documentType\DocumentType',
        ],
        'designation' => [
            'class' => 'backend\modules\designation\Designation',
        ],
        'documentHolder' => [
            'class' => 'backend\modules\documentHolder\DocumentHolder',
        ],
        'employee' => [
            'class' => 'backend\modules\employee\Employee',
        ],
        'judiciaryActions' => [
            'class' => 'backend\modules\judiciaryActions\JudiciaryActions',
        ],
        'judiciaryCustomersActions' => [
            'class' => 'backend\modules\judiciaryCustomersActions\JudiciaryCustomersActions',
        ],
        'expenseCategories' => [
            'class' => 'backend\modules\expenseCategories\ExpenseCategories',
        ],
        'expenses' => [
            'class' => 'backend\modules\expenses\Expenses',
        ],
        'financialTransaction' => [
            'class' => 'backend\modules\financialTransaction\FinancialTransaction',
        ],
        'followUp' => [
            'class' => 'backend\modules\followUp\FollowUp',
        ],
        'followUpReport' => [
            'class' => 'backend\modules\followUpReport\FollowUpReport',
        ],
        'attendance' => [
            'class' => 'backend\modules\attendance\Attendance',
        ],
        'authAssignment' => [
            'class' => 'backend\modules\authAssignment\AuthAssignment',
        ],
        'collection' => [
            'class' => 'backend\modules\collection\Collection',
        ],
        'contractDocumentFile' => [
            'class' => 'backend\modules\contractDocumentFile\ContractDocumentFile',
        ],
        'contractInstallment' => [
            'class' => 'backend\modules\contractInstallment\ContractInstallment',
        ],
        'contracts' => [
            'class' => 'backend\modules\contracts\Contracts',
        ],
        'followUp' => [
            'class' => 'backend\modules\followUp\FollowUp',
        ],
        'companies' => [
            'class' => 'backend\modules\companies\Companies',
        ],
        'itemsInventoryInvoices' => [
            'class' => 'backend\modules\itemsInventoryInvoices\ItemsInventoryInvoices',
        ],
        'inventoryInvoices' => [
            'class' => 'backend\modules\inventoryInvoices\InventoryInvoices',
        ],
        'reports' => [
            'class' => 'backend\modules\reports\Reports',
        ],
        'customers' => [
            'class' => 'backend\modules\customers\Customers',
        ],

        'court' => [
            'class' => 'backend\modules\court\Court',
        ],
        'JudiciaryInformAddress'=>
        [
            'class' => 'backend\modules\JudiciaryInformAddress\JudiciaryInformAddress',
        ],
        'gridview' => [
            'class' => '\kartik\grid\Module'
        ],
        // 'v1' => [
        //     'basePath' => '@api/modules/v1',
        //     'class' => 'api\modules\v1\Module',
        // ],
        'imagemanager' => [
            'class' => 'noam148\imagemanager\Module',
            // 'class' => 'backend\modules\imagemanager\ImageManagerModule',
            //set accces rules ()
            'canUploadImage' => true,
            'canRemoveImage' => function () {
                return true;
            },
            'deleteOriginalAfterEdit' => false, // false: keep original image after edit. true: delete original image after edit
            // Set if blameable behavior is used, if it is, callable function can also be used
            'setBlameableBehavior' => false,
            //add css files (to use in media manage selector iframe)
            'cssFiles' => [
                // 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.3/css/font-awesome.min.css',
            ],
        ],
        'admin' => [
            'class' => 'mdm\admin\Module',
        ]
    ],
    'controllerMap' => [
        'imagemanager' => 'backend\controllers\ImageManagerController',

    ],
    'components' => [
        'customersInformation' => [
            'class' => 'common\components\customersInformation'
        ],
        'City' => [
            'class' => 'common\components\City'
        ],
        'companyChecked' => [
            'class' => 'common\components\CompanyChecked'
        ],
        'PHPExcel_Cell_DefaultValueBinder' => [
            'class' => 'common/overridden/DefaultValueBinder.php',
        ],
        'notifications' => [
            'class' => 'common\components\notificationComponent',
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager', // or use 'yii\rbac\DbManager'
            'cache' => 'yii\caching\FileCache',
        ],
        'request' => [
            'csrfParam' => '_csrf-backend',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-website', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@dektrium/user/views' => '@backend/views/user'
                ],
            ],
        ],
        'assetManager' => [
            // 'forceCopy' => true,
            'appendTimestamp' => true,
            'hashCallback' => function ($path) {
                return hash('md4', $path);
            },
            'bundles' => [
                'yii\web\JqueryAsset' => [
                    'sourcePath' => null, // do not publish the bundle
                    'js' => [
                        '//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js',
                    ]
                ],
                'backend\assets\ImageManagerInputAsset' => [
                    'basePath' => '@webroot',
                    'baseUrl' => '@web',
                    // 'sourcePath' => null, // do not publish the bundle
                    'js' => [
                        'js/script.imagemanager.input.js',
                    ],
                    'css' => [
                        'css/imagemanager.input.css',
                    ]
                ],
                'backend\assets\ImageManagerModuleAsset' => [
                    // 'sourcePath' => null,
                    'basePath' => '@webroot',
                    'baseUrl' => '@web',
                    // 'sourcePath' => '@backend/web', // do not publish the bundle
                    'js' => [
                        'js/cropper.min.js',
                        'js/script.imagemanager.module.js',
                    ],
                    'css' => [
                        'css/cropper.min.css',
                        'css/imagemanager.module.css'
                    ]
                ],
            ],
        ],
    ],
    /* 'as access' => [
     'class' => 'mdm\admin\components\AccessControl',
     'allowActions' => [
     'site/*',
     ]
     ],*/
    'params' => $params,
];
