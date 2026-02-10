<?php

use yii\helpers\Url;
use common\helper\Permissions;

$mainMenuItems = [

    [
        'label' => Yii::t('app', 'Reports'),
        'items' => [
            ['label' => Yii::t('app', 'Total customer payments'), 'url' => Url::to(['/reports/reports/total-customer-payments-index']), 'privilege' => Permissions::REPORTS],
            ['label' => Yii::t('app', 'judiciary report'), 'url' => Url::to(['/reports/reports/judiciary-index']), 'privilege' => Permissions::REPORTS],
            ['label' => Yii::t('app', 'Follow Up Reports'), 'url' => Url::to(['/reports/reports/index2']), 'privilege' => Permissions::REPORTS],
            ['label' => Yii::t('app', 'customers judiciary actions Report'), 'url' => Url::to(['/reports/reports/customers-judiciary-actions']), 'privilege' => Permissions::REPORTS],

        ],
    ],
    [
        'label' => Yii::t('app', 'Inventory'),
        'items' => [
            ['label' => Yii::t('app', 'Inventory Items'), 'url' => Url::to(['/inventoryItems/inventory-items']), 'privilege' => Permissions::INVENTORY_ITEMS],
            ['label' => Yii::t('app', 'Stock Locations'), 'url' => Url::to(['/inventoryStockLocations/inventory-stock-locations']), 'privilege' => Permissions::INVENTORY_STOCK_LOCATIONS],
            ['label' => Yii::t('app', 'suppliers'), 'url' => Url::to(['/inventorySuppliers/inventory-suppliers']), 'privilege' => Permissions::INVENTORY_SUPPLIERS],
          ['label' => Yii::t('app', 'Item Quantities'), 'url' => Url::to(['/inventoryItemQuantities/inventory-item-quantities']), 'privilege' => Permissions::INVENTORY_ITEMS_QUANTITY],
          ['label' => Yii::t('app', 'Inventory Invoices'), 'url' => Url::to(['/inventoryInvoices/inventory-invoices']), 'privilege' => Permissions::INVENTORY_INVOICES],
          ['label' => Yii::t('app', 'Inventory Item Query'), 'url' => Url::to(['/inventoryItems/inventory-items/item-query']), 'privilege' => Permissions::INVENTORY_IEMS_QUERY],

        ],
    ],
    [
        'label' => Yii::t('app', 'legal department'),
        'items' => [
            ['label' => Yii::t('app', 'Transfer to legal department'), 'icon' => 'dashboard', 'url' => ['/contracts/contracts/legal-department'], 'privilege' => Permissions::TRANSFER_TO_LEGAL_DEPARTMENT],
           ['label' => Yii::t('app', 'Judiciary'), 'icon' => 'dashboard', 'url' => ['/judiciary/judiciary'], 'privilege' => Permissions::JUDICIARY],
          ['label' => 'كشف المثابره', 'icon' => 'line-chart', 'url' => ['/judiciary/judiciary/cases-report'], 'privilege' => Permissions::JUDICIARY],
          ['label' => Yii::t('app', 'Judiciary Customers Actions'), 'icon' => 'dashboard', 'url' => ['/judiciaryCustomersActions/judiciary-customers-actions'], 'privilege' => Permissions::JUDICIARY_CUSTOMERS_ACTION],
            ['label' => Yii::t('app', 'Collection'), 'icon' => 'dashboard', 'url' => ['/collection/collection'], 'privilege' => Permissions::COLLECTION],

        ],
    ],
    [
        'label' => Yii::t('app', 'ادارة الموظفين'),
        'items' => [
            ['label' => Yii::t('app', 'Employees'), 'icon' => 'dashboard', 'url' => ['/employee/employee'], 'privilege' => Permissions::EMPLOYEE],
            ['label' => Yii::t('app', 'Holidays'), 'icon' => 'dashboard', 'url' => ['/holidays/holidays'], 'privilege' => Permissions::HOLIDAYS],
            ['label' => Yii::t('app', 'Leave Policy'), 'icon' => 'dashboard', 'url' => ['/leavePolicy/leave-policy'], 'privilege' => Permissions::LEAVE_POLICY],
            ['label' => Yii::t('app', 'Leave Types'), 'icon' => 'dashboard', 'url' => ['/leaveTypes/leave-types'], 'privilege' => Permissions::LEAVE_TYPES],
            ['label' => Yii::t('app', 'Workdays'), 'icon' => 'dashboard', 'url' => ['/workdays/workdays'], 'privilege' => Permissions::WORKDAYS],
            ['label' => Yii::t('app', 'Leave Request'), 'icon' => 'dashboard', 'url' => ['/leaveRequest/leave-request'], 'privilege' => Permissions::LEAVE_REQUEST],
            ['label' => Yii::t('app', 'suspended vacations'), 'icon' => 'dashboard', 'url' => ['/leaveRequest/leave-request/suspended-vacations'], 'privilege' => Permissions::LEAVE_REQUEST],
        ],
    ],
    [
        'label' => Yii::t('app', 'متابعة الملفات'),
        'items' => [

            ['label' => Yii::t('app', 'Document Holder'), 'icon' => 'dashboard', 'url' => ['/documentHolder/document-holder'], 'privilege' => Permissions::DOCUMENT_HOLDER],
            ['label' => Yii::t('app', 'Manager Document Holder'), 'icon' => 'dashboard', 'url' => ['/documentHolder/document-holder/manager-document-holder'], 'privilege' => Permissions::MANAGER],
        ],
    ],
    /* ═══ إدارة الصلاحيات — شاشة موحدة ═══ */
    ['label' => 'إدارة الصلاحيات', 'icon' => 'shield', 'url' => ['/permissions-management'], 'privilege' => [Permissions::PERMISSION, Permissions::ROLE, Permissions::ASSIGNMENT]],

    [
        'label' => Yii::t('app', 'ادارة المتغيرات'),
        'items' => [
            ['label' => Yii::t('app', 'Status'), 'icon' => 'dashboard', 'url' => ['/status/status/index'], 'privilege' => Permissions::STATUS],
            ['label' => Yii::t('app', 'Document Status'), 'icon' => 'dashboard', 'url' => ['/documentStatus/document-status/index'], 'privilege' => Permissions::Document_STATUS],
            ['label' => Yii::t('app', 'Cousins'), 'icon' => 'dashboard', 'url' => ['/cousins/cousins/index'], 'privilege' => Permissions::COUSINS],
            ['label' => Yii::t('app', 'Citizen'), 'icon' => 'dashboard', 'url' => ['/citizen/citizen/index'], 'privilege' => Permissions::CITIZEN],
            ['label' => Yii::t('app', 'Bancks'), 'icon' => 'dashboard', 'url' => ['/bancks/bancks/index'], 'privilege' => Permissions::BANCKS],
            ['label' => Yii::t('app', 'Hear About Us'), 'icon' => 'dashboard', 'url' => ['/hearAboutUs/hear-about-us/index'], 'privilege' => Permissions::HEAR_ABOUT_US],
            ['label' => Yii::t('app', 'City'), 'icon' => 'dashboard', 'url' => ['/city/city/index'], 'privilege' => Permissions::CITY],
            ['label' => Yii::t('app', 'Payment Type'), 'icon' => 'dashboard', 'url' => ['/paymentType/payment-type/index'], 'privilege' => Permissions::PAYMENT_TYPE],
            ['label' => Yii::t('app', 'feelings'), 'icon' => 'dashboard', 'url' => ['/feelings/feelings/index'], 'privilege' => Permissions::FEELINGS],
            ['label' => Yii::t('app', 'Contact Type'), 'icon' => 'dashboard', 'url' => ['/contactType/contact-type/index'], 'privilege' => Permissions::CONTACT_TYPE],
            ['label' => Yii::t('app', 'Connection Response'), 'icon' => 'dashboard', 'url' => ['/connectionResponse/connection-response/index'], 'privilege' => Permissions::CONNECTION_RESPONSE],
            ['label' => Yii::t('app', 'Document Type'), 'icon' => 'dashboard', 'url' => ['/documentType/document-type/index'], 'privilege' => Permissions::DOCYUMENT_TYPE],
            ['label' => Yii::t('app', 'Judiciary Actions'), 'icon' => 'dashboard', 'url' => ['/judiciaryActions/judiciary-actions'], 'privilege' => Permissions::JUDICIARY_ACTION],
            ['label' => Yii::t('app', 'Judiciary Type'), 'icon' => 'dashboard', 'url' => ['/judiciaryType/judiciary-type'], 'privilege' => Permissions::JUDICIARY_TYPE],
            ['label' => Yii::t('app', 'Lawyers'), 'icon' => 'dashboard', 'url' => ['/lawyers/lawyers'], 'privilege' => Permissions::LAWYERS],
            ['label' => Yii::t('app', 'Court'), 'icon' => 'dashboard', 'url' => ['/court/court'], 'privilege' => Permissions::COURT],
            ['label' => Yii::t('app', 'Massages'), 'icon' => 'dashboard', 'url' => ['/sms/sms'], 'privilege' => Permissions::COURT],
            ['label' => Yii::t('app', 'Jobs'), 'icon' => 'dashboard', 'url' => ['/jobs/jobs'], 'privilege' => Permissions::JOBS],
            ['label' => Yii::t('app', 'Expense Categories'), 'icon' => 'dashboard', 'url' => ['/expenseCategories/expense-categories'], 'privilege' => Permissions::EXPENSE_CATEGORIES],
            ['label' => Yii::t('app', 'Designation'), 'icon' => 'dashboard', 'url' => ['/designation/designation'], 'privilege' => Permissions::EXPENSE_CATEGORIES],
            ['label' => Yii::t('app', 'judiciary_inform_address'), 'icon' => 'dashboard', 'url' => ['/JudiciaryInformAddress/judiciary-inform-address'], 'privilege' => Permissions::JUDICIARY_INFORM_ADDRESS],

        ]
    ],
    ['label' => Yii::t('app', 'Main Menu'), 'options' => ['class' => 'header']],
    ['label' => Yii::t('app', 'Customers'), 'icon' => 'dashboard', 'url' => ['/customers/customers'], 'privilege' => Permissions::CUSTOMERS],
  ['label' => Yii::t('app', 'Determination'), 'icon' => 'dashboard', 'url' => ['/determination'], 'privilege' => Permissions::DETERMINATION],
    ['label' => Yii::t('app', 'Investors'), 'icon' => 'dashboard', 'url' => ['/companies/companies'], 'privilege' => Permissions::COMPAINES],

    ['label' => Yii::t('app', 'Contracts'), 'icon' => 'dashboard', 'url' => ['/contracts/contracts'], 'privilege' => Permissions::CONTRACTS],
    ['label' => Yii::t('app', 'Follow Up Report'), 'icon' => 'dashboard', 'url' => ['/followUpReport/follow-up-report'], 'privilege' => Permissions::FOLLOW_UP_REPORT],
    /* ═══ الإدارة المالية — عنصر واحد يشمل الأربعة (OR logic) ═══ */
    ['label' => 'الإدارة المالية', 'icon' => 'briefcase', 'url' => ['/financialTransaction/financial-transaction'], 'privilege' => [Permissions::FINANCIAL_TRANSACTION, Permissions::INCOME, Permissions::EXPENSES, Permissions::LOAN_SCHEDULING]],
    ['label' => 'Login', 'url' => ['site/login'], 'visible' => Yii::$app->user->isGuest],
];
return Permissions::checkMainMenuItems($mainMenuItems);