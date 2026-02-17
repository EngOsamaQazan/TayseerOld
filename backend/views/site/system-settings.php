<?php
/**
 * إعدادات النظام — System Settings
 * @var yii\web\View $this
 * @var array $googleCloud
 * @var array $usageStats
 * @var string $activeTab
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'إعدادات النظام';

// Register CSS
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/system-settings.css?v=' . time());
?>

<div class="sys-settings-page">
    <!-- ════════════ Header ════════════ -->
    <div class="sys-header">
        <div class="sys-header-right">
            <h1><i class="fa fa-cogs"></i> إعدادات النظام</h1>
            <p class="sys-header-desc">إدارة الإعدادات العامة وتكامل الخدمات الخارجية</p>
        </div>
        <div class="sys-header-left">
            <a href="<?= Url::to(['/site/index']) ?>" class="sys-back-btn">
                <i class="fa fa-arrow-right"></i> العودة للوحة التحكم
            </a>
        </div>
    </div>

    <!-- ════════════ Body ════════════ -->
    <div class="sys-body">
        <!-- Sidebar Tabs -->
        <div class="sys-sidebar">
            <div class="sys-nav-list">
                <a href="#" class="sys-nav-item <?= $activeTab === 'general' ? 'active' : '' ?>" data-tab="general">
                    <div class="sys-nav-icon"><i class="fa fa-sliders"></i></div>
                    <div class="sys-nav-text">
                        <span class="sys-nav-label">الإعدادات العامة</span>
                        <span class="sys-nav-sub">المتغيرات · الجداول المرجعية</span>
                    </div>
                    <span class="sys-nav-count">20</span>
                </a>
                <a href="#" class="sys-nav-item <?= $activeTab === 'google_cloud' ? 'active' : '' ?>" data-tab="google_cloud">
                    <div class="sys-nav-icon"><i class="fa fa-eye"></i></div>
                    <div class="sys-nav-text">
                        <span class="sys-nav-label">تحليل الوثائق بالذكاء الاصطناعي</span>
                        <span class="sys-nav-sub">تصنيف الصور والمستندات تلقائياً</span>
                    </div>
                    <?php if (!empty($googleCloud['enabled']) && $googleCloud['enabled'] === '1'): ?>
                        <span class="sys-nav-badge active"><i class="fa fa-check-circle"></i></span>
                    <?php else: ?>
                        <span class="sys-nav-badge inactive"><i class="fa fa-times-circle"></i></span>
                    <?php endif; ?>
                </a>
                <a href="#" class="sys-nav-item <?= $activeTab === 'google_maps' ? 'active' : '' ?>" data-tab="google_maps">
                    <div class="sys-nav-icon"><i class="fa fa-map"></i></div>
                    <div class="sys-nav-text">
                        <span class="sys-nav-label">خريطة Google</span>
                        <span class="sys-nav-sub">مفتاح API لخريطة تتبع الموظفين</span>
                    </div>
                    <?php if (!empty($googleMaps['configured'])): ?>
                        <span class="sys-nav-badge active"><i class="fa fa-check-circle"></i></span>
                    <?php else: ?>
                        <span class="sys-nav-badge inactive"><i class="fa fa-times-circle"></i></span>
                    <?php endif; ?>
                </a>
                <a href="#" class="sys-nav-item disabled" data-tab="notifications">
                    <div class="sys-nav-icon"><i class="fa fa-bell"></i></div>
                    <div class="sys-nav-text">
                        <span class="sys-nav-label">الإشعارات</span>
                        <span class="sys-nav-sub">قريباً</span>
                    </div>
                </a>
                <hr style="border-color:#eee;margin:8px 0;" />
                <a href="<?= Url::to(['/site/image-manager']) ?>" class="sys-nav-item">
                    <div class="sys-nav-icon"><i class="fa fa-image"></i></div>
                    <div class="sys-nav-text">
                        <span class="sys-nav-label">إدارة صور العملاء</span>
                        <span class="sys-nav-sub">مراجعة · تصحيح · تصدير</span>
                    </div>
                    <span class="sys-nav-badge" style="background:var(--gold);color:#fff;"><i class="fa fa-wrench"></i></span>
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="sys-content">
            <?php foreach (Yii::$app->session->getAllFlashes() as $type => $message): ?>
                <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible sys-alert">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fa fa-<?= $type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                    <?= $message ?>
                </div>
            <?php endforeach; ?>

            <!-- ═══════════ Google Cloud Tab ═══════════ -->
            <div class="sys-tab-content <?= $activeTab === 'google_cloud' ? 'active' : '' ?>" id="tab-google_cloud">
                <form method="post" action="<?= Url::to(['system-settings']) ?>" id="gc-settings-form">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <input type="hidden" name="settings_tab" value="google_cloud">

                    <!-- Connection Status Card -->
                    <div class="sys-card sys-status-card">
                        <div class="sys-card-header">
                            <div class="sys-card-title">
                                <i class="fa fa-signal"></i> حالة الاتصال
                            </div>
                            <button type="button" class="sys-test-btn" id="btn-test-connection" onclick="testGoogleConnection()">
                                <i class="fa fa-plug"></i> اختبار الاتصال
                            </button>
                        </div>
                        <div class="sys-card-body">
                            <div class="sys-connection-status" id="connection-status">
                                <?php if (!empty($googleCloud['enabled']) && $googleCloud['enabled'] === '1' && !empty($googleCloud['has_private_key'])): ?>
                                    <div class="sys-status-indicator configured">
                                        <i class="fa fa-check-circle fa-2x"></i>
                                        <div>
                                            <strong>تم التكوين</strong>
                                            <p>بيانات الاعتماد محفوظة — اضغط "اختبار الاتصال" للتحقق</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="sys-status-indicator not-configured">
                                        <i class="fa fa-exclamation-circle fa-2x"></i>
                                        <div>
                                            <strong>غير مكوّن</strong>
                                            <p>أدخل بيانات اعتماد حساب الخدمة لتفعيل التكامل مع Google Cloud</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Toggle Enable -->
                    <div class="sys-card">
                        <div class="sys-card-body">
                            <div class="sys-toggle-row">
                                <div class="sys-toggle-info">
                                    <i class="fa fa-power-off sys-toggle-icon"></i>
                                    <div>
                                        <strong>تفعيل Google Cloud Vision API</strong>
                                        <p>عند التفعيل، سيتم استخدام التصنيف الذكي لتحليل مستندات العملاء تلقائياً</p>
                                    </div>
                                </div>
                                <label class="sys-switch">
                                    <input type="hidden" name="gc_enabled" value="0">
                                    <input type="checkbox" name="gc_enabled" value="1" <?= (!empty($googleCloud['enabled']) && $googleCloud['enabled'] === '1') ? 'checked' : '' ?>>
                                    <span class="sys-switch-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════ Setup Guide (Collapsible) ═══════════ -->
                    <div class="sys-card sys-guide-card">
                        <div class="sys-card-header sys-guide-toggle" onclick="toggleGuide()">
                            <div class="sys-card-title">
                                <i class="fa fa-graduation-cap"></i> دليل الإعداد خطوة بخطوة
                            </div>
                            <div class="sys-guide-toggle-hint">
                                <span id="guide-toggle-text">عرض الدليل</span>
                                <i class="fa fa-chevron-down" id="guide-chevron"></i>
                            </div>
                        </div>
                        <div class="sys-guide-body" id="setup-guide" style="display:none;">

                            <!-- Step Progress -->
                            <div class="gc-steps-progress">
                                <div class="gc-step-dot active" data-step="1"><span>1</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="2"><span>2</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="3"><span>3</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="4"><span>4</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="5"><span>5</span></div>
                                <div class="gc-step-line"></div>
                                <div class="gc-step-dot" data-step="6"><span>6</span></div>
                            </div>

                            <!-- ──── Step 1: Create Project ──── -->
                            <div class="gc-step active" id="gc-step-1">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">1</div>
                                    <div>
                                        <h3>إنشاء مشروع في Google Cloud</h3>
                                        <p>أول خطوة هي إنشاء مشروع جديد أو استخدام مشروع موجود</p>
                                    </div>
                                </div>

                                <!-- Console Mockup -->
                                <div class="gc-console-mockup">
                                    <div class="gc-console-topbar">
                                        <div class="gc-console-logo">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#fff"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                            <span>Google Cloud</span>
                                        </div>
                                        <div class="gc-console-breadcrumb">Console → New Project</div>
                                    </div>
                                    <div class="gc-console-content">
                                        <div class="gc-console-sidebar-mini">
                                            <div class="gc-sidebar-item">IAM & Admin</div>
                                            <div class="gc-sidebar-item">APIs & Services</div>
                                            <div class="gc-sidebar-item active-item">Manage Resources</div>
                                        </div>
                                        <div class="gc-console-main">
                                            <div class="gc-console-title-bar">
                                                <span class="gc-console-page-title">New Project</span>
                                            </div>
                                            <div class="gc-mock-form">
                                                <div class="gc-mock-field">
                                                    <label>Project name *</label>
                                                    <div class="gc-mock-input gc-highlight-field">tayseer-erp</div>
                                                </div>
                                                <div class="gc-mock-field">
                                                    <label>Project ID</label>
                                                    <div class="gc-mock-input">tayseer-erp-438712 <span class="gc-mock-edit">Edit</span></div>
                                                    <div class="gc-mock-hint">
                                                        <i class="fa fa-arrow-up gc-arrow-blink"></i>
                                                        <span class="gc-highlight-text">هذا هو Project ID الذي ستنسخه لاحقاً</span>
                                                    </div>
                                                </div>
                                                <div class="gc-mock-field">
                                                    <label>Organization</label>
                                                    <div class="gc-mock-input">No organization</div>
                                                </div>
                                                <div class="gc-mock-field">
                                                    <label>Location</label>
                                                    <div class="gc-mock-input">No organization</div>
                                                </div>
                                                <div class="gc-mock-btn-row">
                                                    <button class="gc-mock-btn primary" type="button">CREATE</button>
                                                    <button class="gc-mock-btn" type="button">CANCEL</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>اذهب إلى <a href="https://console.cloud.google.com/projectcreate" target="_blank" class="gc-link">console.cloud.google.com/projectcreate</a></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>أدخل اسم المشروع (مثلاً: <code>tayseer-erp</code>)</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>سيتم توليد <strong>Project ID</strong> تلقائياً — <em>احفظه، ستحتاجه في الخطوة الأخيرة</em></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">4</span>
                                        <span>اضغط <strong>CREATE</strong></span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <div></div>
                                    <button type="button" class="gc-next-btn" onclick="goToStep(2)">الخطوة التالية <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- ──── Step 2: Enable Vision API ──── -->
                            <div class="gc-step" id="gc-step-2">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">2</div>
                                    <div>
                                        <h3>تفعيل Vision API</h3>
                                        <p>تفعيل خدمة Cloud Vision API في مشروعك</p>
                                    </div>
                                </div>

                                <div class="gc-console-mockup">
                                    <div class="gc-console-topbar">
                                        <div class="gc-console-logo">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#fff"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                            <span>Google Cloud</span>
                                        </div>
                                        <div class="gc-console-breadcrumb">APIs & Services → Library</div>
                                    </div>
                                    <div class="gc-console-content">
                                        <div class="gc-console-main" style="max-width:100%">
                                            <div class="gc-console-title-bar">
                                                <span class="gc-console-page-title">API Library</span>
                                            </div>
                                            <div class="gc-mock-search">
                                                <i class="fa fa-search"></i>
                                                <span>Cloud Vision API</span>
                                            </div>
                                            <div class="gc-api-card">
                                                <div class="gc-api-card-icon">
                                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="#4285f4"><circle cx="12" cy="12" r="3.2"/><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" fill="none" stroke="#4285f4" stroke-width="1.5"/></svg>
                                                </div>
                                                <div class="gc-api-card-info">
                                                    <strong>Cloud Vision API</strong>
                                                    <p>Integrates Google Vision features including image labeling, face, logo, and landmark detection, optical character recognition (OCR)...</p>
                                                    <div class="gc-api-card-meta">Google Enterprise API</div>
                                                </div>
                                                <button class="gc-mock-btn primary gc-enable-btn" type="button">
                                                    <i class="fa fa-check"></i> ENABLE
                                                </button>
                                            </div>
                                            <div class="gc-mock-hint" style="margin-top:10px;">
                                                <i class="fa fa-arrow-up gc-arrow-blink"></i>
                                                <span class="gc-highlight-text">اضغط ENABLE لتفعيل الخدمة</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>اذهب إلى <a href="https://console.cloud.google.com/apis/library/vision.googleapis.com" target="_blank" class="gc-link">APIs & Services → Library</a></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>ابحث عن <code>Cloud Vision API</code></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>اضغط <strong>ENABLE</strong> لتفعيل الخدمة</span>
                                    </div>
                                    <div class="gc-instruction-item gc-note-item">
                                        <i class="fa fa-info-circle"></i>
                                        <span>أول 1,000 طلب شهرياً <strong>مجاني</strong> — بعدها $1.50 لكل 1,000 طلب</span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToStep(1)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn" onclick="goToStep(3)">الخطوة التالية <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- ──── Step 3: Create Service Account ──── -->
                            <div class="gc-step" id="gc-step-3">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">3</div>
                                    <div>
                                        <h3>إنشاء حساب خدمة (Service Account)</h3>
                                        <p>حساب الخدمة يسمح للنظام بالتواصل مع Google Cloud بدون تدخل بشري</p>
                                    </div>
                                </div>

                                <div class="gc-console-mockup">
                                    <div class="gc-console-topbar">
                                        <div class="gc-console-logo">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#fff"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                            <span>Google Cloud</span>
                                        </div>
                                        <div class="gc-console-breadcrumb">IAM & Admin → Service Accounts</div>
                                    </div>
                                    <div class="gc-console-content">
                                        <div class="gc-console-main" style="max-width:100%">
                                            <div class="gc-console-title-bar">
                                                <span class="gc-console-page-title">Service Accounts</span>
                                                <button class="gc-mock-btn primary gc-create-sa-btn" type="button">
                                                    <i class="fa fa-plus"></i> CREATE SERVICE ACCOUNT
                                                </button>
                                            </div>
                                            <div class="gc-mock-form" style="margin-top:12px;">
                                                <div class="gc-mock-field">
                                                    <label>Service account name *</label>
                                                    <div class="gc-mock-input gc-highlight-field">tayseer-vision</div>
                                                </div>
                                                <div class="gc-mock-field">
                                                    <label>Service account ID *</label>
                                                    <div class="gc-mock-input">tayseer-vision@tayseer-erp.iam.gserviceaccount.com</div>
                                                    <div class="gc-mock-hint">
                                                        <i class="fa fa-arrow-up gc-arrow-blink"></i>
                                                        <span class="gc-highlight-text">هذا هو Client Email الذي ستنسخه لاحقاً</span>
                                                    </div>
                                                </div>
                                                <div class="gc-mock-field">
                                                    <label>Service account description</label>
                                                    <div class="gc-mock-input">Vision API for document classification</div>
                                                </div>
                                                <div class="gc-mock-btn-row">
                                                    <button class="gc-mock-btn primary" type="button">CREATE AND CONTINUE</button>
                                                    <button class="gc-mock-btn" type="button">CANCEL</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>اذهب إلى <a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" class="gc-link">IAM & Admin → Service Accounts</a></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>اضغط <strong>+ CREATE SERVICE ACCOUNT</strong> في الأعلى</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>أدخل اسم حساب الخدمة (مثلاً: <code>tayseer-vision</code>)</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">4</span>
                                        <span>اضغط <strong>CREATE AND CONTINUE</strong></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">5</span>
                                        <span>في "Grant this service account access" — اختر الدور: <code>Cloud Vision AI Service Agent</code> أو <code>Editor</code></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">6</span>
                                        <span>اضغط <strong>DONE</strong></span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToStep(2)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn" onclick="goToStep(4)">الخطوة التالية <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- ──── Step 4: Create JSON Key ──── -->
                            <div class="gc-step" id="gc-step-4">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">4</div>
                                    <div>
                                        <h3>إنشاء مفتاح JSON</h3>
                                        <p>تحميل ملف المفتاح الذي يحتوي على بيانات الاعتماد المطلوبة</p>
                                    </div>
                                </div>

                                <div class="gc-console-mockup">
                                    <div class="gc-console-topbar">
                                        <div class="gc-console-logo">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="#fff"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                            <span>Google Cloud</span>
                                        </div>
                                        <div class="gc-console-breadcrumb">Service Account → Keys</div>
                                    </div>
                                    <div class="gc-console-content">
                                        <div class="gc-console-main" style="max-width:100%">
                                            <div class="gc-sa-tabs">
                                                <span>DETAILS</span>
                                                <span>PERMISSIONS</span>
                                                <span class="active-tab">KEYS</span>
                                            </div>
                                            <div class="gc-console-title-bar" style="margin-top:10px;">
                                                <span class="gc-console-page-title">Keys</span>
                                                <button class="gc-mock-btn primary" type="button">
                                                    <i class="fa fa-plus"></i> ADD KEY ▾
                                                </button>
                                            </div>
                                            <div class="gc-dropdown-menu">
                                                <div class="gc-dropdown-item gc-highlight-field">
                                                    <i class="fa fa-key"></i> Create new key
                                                </div>
                                                <div class="gc-dropdown-item">
                                                    <i class="fa fa-upload"></i> Upload existing key
                                                </div>
                                            </div>
                                            <div class="gc-key-type-dialog">
                                                <div class="gc-dialog-title">Create private key</div>
                                                <div class="gc-dialog-body">
                                                    <div class="gc-radio-option selected">
                                                        <div class="gc-radio-circle selected"></div>
                                                        <div>
                                                            <strong>JSON</strong>
                                                            <span class="gc-recommended-badge">Recommended</span>
                                                            <p>Downloads a JSON file with the key</p>
                                                        </div>
                                                    </div>
                                                    <div class="gc-radio-option">
                                                        <div class="gc-radio-circle"></div>
                                                        <div>
                                                            <strong>P12</strong>
                                                            <p>For backward compatibility with code using P12 format</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="gc-mock-btn-row" style="border-top:1px solid #e0e0e0; padding-top:12px;">
                                                    <button class="gc-mock-btn primary" type="button">CREATE</button>
                                                    <button class="gc-mock-btn" type="button">CANCEL</button>
                                                </div>
                                            </div>
                                            <div class="gc-mock-hint" style="margin-top:10px;">
                                                <i class="fa fa-arrow-up gc-arrow-blink"></i>
                                                <span class="gc-highlight-text">اختر JSON ثم اضغط CREATE — سيتم تحميل الملف تلقائياً</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>اضغط على حساب الخدمة الذي أنشأته في الخطوة السابقة</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>اذهب إلى تبويب <strong>KEYS</strong></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">3</span>
                                        <span>اضغط <strong>ADD KEY</strong> → <strong>Create new key</strong></span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">4</span>
                                        <span>اختر <strong>JSON</strong> ثم اضغط <strong>CREATE</strong></span>
                                    </div>
                                    <div class="gc-instruction-item gc-note-item gc-warning-item">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <span>سيتم تحميل ملف <code>.json</code> تلقائياً — <strong>احفظه في مكان آمن!</strong> لن تتمكن من تحميله مرة أخرى</span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToStep(3)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn" onclick="goToStep(5)">الخطوة التالية <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- ──── Step 5: Read JSON File ──── -->
                            <div class="gc-step" id="gc-step-5">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">5</div>
                                    <div>
                                        <h3>قراءة ملف JSON واستخراج البيانات</h3>
                                        <p>افتح الملف الذي تم تحميله واستخرج الحقول المطلوبة</p>
                                    </div>
                                </div>

                                <!-- JSON File Mockup -->
                                <div class="gc-json-viewer">
                                    <div class="gc-json-toolbar">
                                        <div class="gc-json-filename">
                                            <i class="fa fa-file-code-o"></i> tayseer-erp-438712-a1b2c3d4e5f6.json
                                        </div>
                                        <span class="gc-json-size">2.3 KB</span>
                                    </div>
                                    <div class="gc-json-content" dir="ltr">
                                        <div class="gc-json-line"><span class="gc-json-brace">{</span></div>
                                        <div class="gc-json-line gc-json-dim">  <span class="gc-json-key">"type"</span>: <span class="gc-json-string">"service_account"</span>,</div>
                                        <div class="gc-json-line gc-json-highlight" data-field="project_id">
                                            <div class="gc-json-marker" data-label="1">
                                                <i class="fa fa-arrow-left gc-arrow-blink"></i> انسخ هذا → <strong>Project ID</strong>
                                            </div>
                                            &nbsp;&nbsp;<span class="gc-json-key">"project_id"</span>: <span class="gc-json-string gc-json-copyable" onclick="copyJsonValue(this, 'gc_project_id')">"tayseer-erp-438712"</span>,
                                        </div>
                                        <div class="gc-json-line gc-json-dim">  <span class="gc-json-key">"private_key_id"</span>: <span class="gc-json-string">"a1b2c3d4..."</span>,</div>
                                        <div class="gc-json-line gc-json-highlight" data-field="private_key">
                                            <div class="gc-json-marker" data-label="3">
                                                <i class="fa fa-arrow-left gc-arrow-blink"></i> انسخ هذا → <strong>المفتاح الخاص</strong>
                                            </div>
                                            &nbsp;&nbsp;<span class="gc-json-key">"private_key"</span>: <span class="gc-json-string">"-----BEGIN RSA PRIVATE KEY-----\nMIIE..."</span>,
                                        </div>
                                        <div class="gc-json-line gc-json-highlight" data-field="client_email">
                                            <div class="gc-json-marker" data-label="2">
                                                <i class="fa fa-arrow-left gc-arrow-blink"></i> انسخ هذا → <strong>Client Email</strong>
                                            </div>
                                            &nbsp;&nbsp;<span class="gc-json-key">"client_email"</span>: <span class="gc-json-string gc-json-copyable" onclick="copyJsonValue(this, 'gc_client_email')">"tayseer-vision@tayseer-erp.iam.gserviceaccount.com"</span>,
                                        </div>
                                        <div class="gc-json-line gc-json-dim">  <span class="gc-json-key">"client_id"</span>: <span class="gc-json-string">"1234567890"</span>,</div>
                                        <div class="gc-json-line gc-json-dim">  <span class="gc-json-key">"auth_uri"</span>: <span class="gc-json-string">"https://accounts.google.com/o/oauth2/auth"</span>,</div>
                                        <div class="gc-json-line gc-json-dim">  <span class="gc-json-key">"token_uri"</span>: <span class="gc-json-string">"https://oauth2.googleapis.com/token"</span>,</div>
                                        <div class="gc-json-line gc-json-dim">  <span class="gc-json-key">"..."</span>: <span class="gc-json-string">"..."</span></div>
                                        <div class="gc-json-line"><span class="gc-json-brace">}</span></div>
                                    </div>
                                </div>

                                <!-- Field Mapping -->
                                <div class="gc-field-mapping">
                                    <div class="gc-mapping-title"><i class="fa fa-exchange"></i> ربط الحقول</div>
                                    <div class="gc-mapping-grid">
                                        <div class="gc-mapping-row">
                                            <div class="gc-mapping-from">
                                                <span class="gc-mapping-num">1</span>
                                                <code>"project_id"</code>
                                            </div>
                                            <i class="fa fa-long-arrow-left gc-mapping-arrow"></i>
                                            <div class="gc-mapping-to">معرّف المشروع (Project ID)</div>
                                        </div>
                                        <div class="gc-mapping-row">
                                            <div class="gc-mapping-from">
                                                <span class="gc-mapping-num">2</span>
                                                <code>"client_email"</code>
                                            </div>
                                            <i class="fa fa-long-arrow-left gc-mapping-arrow"></i>
                                            <div class="gc-mapping-to">البريد الإلكتروني (Client Email)</div>
                                        </div>
                                        <div class="gc-mapping-row">
                                            <div class="gc-mapping-from">
                                                <span class="gc-mapping-num">3</span>
                                                <code>"private_key"</code>
                                            </div>
                                            <i class="fa fa-long-arrow-left gc-mapping-arrow"></i>
                                            <div class="gc-mapping-to">المفتاح الخاص (Private Key)</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">1</span>
                                        <span>افتح ملف JSON بأي محرر نصوص (Notepad أو VS Code)</span>
                                    </div>
                                    <div class="gc-instruction-item">
                                        <span class="gc-inst-num">2</span>
                                        <span>انسخ قيمة كل حقل (بدون علامات التنصيص <code>" "</code>) إلى الحقل المقابل في النموذج أدناه</span>
                                    </div>
                                    <div class="gc-instruction-item gc-note-item">
                                        <i class="fa fa-lightbulb-o"></i>
                                        <span>يمكنك أيضاً لصق محتوى ملف JSON كاملاً في المربع أدناه وسيتم استخراج البيانات تلقائياً</span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToStep(4)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn" onclick="goToStep(6)">الخطوة الأخيرة <i class="fa fa-arrow-left"></i></button>
                                </div>
                            </div>

                            <!-- ──── Step 6: Paste & Fill ──── -->
                            <div class="gc-step" id="gc-step-6">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">6</div>
                                    <div>
                                        <h3>لصق البيانات وحفظ الإعدادات</h3>
                                        <p>الصق محتوى ملف JSON أو أدخل الحقول يدوياً</p>
                                    </div>
                                </div>

                                <!-- Auto-parse JSON box -->
                                <div class="gc-auto-parse">
                                    <div class="gc-auto-parse-header">
                                        <i class="fa fa-magic"></i>
                                        <strong>استخراج تلقائي من ملف JSON</strong>
                                        <span class="gc-optional-badge">اختياري</span>
                                    </div>
                                    <div class="gc-auto-parse-body">
                                        <p>الصق محتوى ملف JSON كاملاً هنا وسيتم ملء الحقول تلقائياً:</p>
                                        <textarea id="gc-json-paste" class="gc-json-paste-area" dir="ltr" rows="5" placeholder='الصق محتوى ملف JSON هنا... مثال:
{
  "type": "service_account",
  "project_id": "...",
  "client_email": "...",
  "private_key": "-----BEGIN RSA..."
}'></textarea>
                                        <button type="button" class="gc-parse-btn" onclick="parseJsonCredentials()">
                                            <i class="fa fa-magic"></i> استخراج البيانات
                                        </button>
                                        <div id="gc-parse-result" class="gc-parse-result" style="display:none;"></div>
                                    </div>
                                </div>

                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item gc-note-item gc-success-item">
                                        <i class="fa fa-check-circle"></i>
                                        <span>بعد ملء الحقول أدناه، اضغط <strong>"حفظ الإعدادات"</strong> ثم <strong>"اختبار الاتصال"</strong> للتأكد</span>
                                    </div>
                                </div>

                                <div class="gc-step-nav">
                                    <button type="button" class="gc-prev-btn" onclick="goToStep(5)"><i class="fa fa-arrow-right"></i> السابق</button>
                                    <button type="button" class="gc-next-btn gc-done-btn" onclick="toggleGuide()"><i class="fa fa-check"></i> إغلاق الدليل</button>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ═══════════ Credentials Card ═══════════ -->
                    <div class="sys-card">
                        <div class="sys-card-header">
                            <div class="sys-card-title">
                                <i class="fa fa-key"></i> بيانات اعتماد حساب الخدمة
                            </div>
                            <span class="sys-card-badge"><i class="fa fa-lock"></i> مشفّرة</span>
                        </div>
                        <div class="sys-card-body">
                            <div class="sys-form-grid">
                                <div class="sys-field">
                                    <label class="sys-label" for="gc_project_id">
                                        <i class="fa fa-folder-open"></i> معرّف المشروع (Project ID)
                                        <span class="gc-json-field-tag">"project_id"</span>
                                    </label>
                                    <input type="text" class="sys-input" id="gc_project_id" name="gc_project_id"
                                           value="<?= Html::encode($googleCloud['project_id'] ?? '') ?>"
                                           placeholder="my-project-123456"
                                           dir="ltr">
                                </div>

                                <div class="sys-field">
                                    <label class="sys-label" for="gc_client_email">
                                        <i class="fa fa-envelope"></i> البريد الإلكتروني للخدمة (Client Email)
                                        <span class="gc-json-field-tag">"client_email"</span>
                                    </label>
                                    <input type="email" class="sys-input" id="gc_client_email" name="gc_client_email"
                                           value="<?= Html::encode($googleCloud['client_email'] ?? '') ?>"
                                           placeholder="service-account@project.iam.gserviceaccount.com"
                                           dir="ltr">
                                </div>

                                <div class="sys-field sys-field-full">
                                    <label class="sys-label" for="gc_private_key">
                                        <i class="fa fa-shield"></i> المفتاح الخاص (Private Key)
                                        <span class="gc-json-field-tag">"private_key"</span>
                                    </label>
                                    <div class="sys-key-wrapper">
                                        <textarea class="sys-textarea" id="gc_private_key" name="gc_private_key"
                                                  rows="4" dir="ltr"
                                                  placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;MIIEpAIBAAKCAQ...&#10;-----END RSA PRIVATE KEY-----"><?= $googleCloud['has_private_key'] ? '••••••••••' : '' ?></textarea>
                                        <?php if (!empty($googleCloud['has_private_key'])): ?>
                                            <div class="sys-key-notice">
                                                <i class="fa fa-check-circle"></i>
                                                مفتاح خاص محفوظ ومشفّر — اتركه كما هو للاحتفاظ بالمفتاح الحالي، أو الصق مفتاحاً جديداً
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Usage & Limits Card -->
                    <div class="sys-card">
                        <div class="sys-card-header">
                            <div class="sys-card-title">
                                <i class="fa fa-bar-chart"></i> الاستخدام والحدود
                            </div>
                            <span class="sys-card-subtitle"><?= date('F Y') ?></span>
                        </div>
                        <div class="sys-card-body">
                            <div class="sys-stats-grid">
                                <div class="sys-stat-box">
                                    <div class="sys-stat-value"><?= number_format($usageStats['total_requests']) ?></div>
                                    <div class="sys-stat-label">إجمالي الطلبات</div>
                                </div>
                                <div class="sys-stat-box success">
                                    <div class="sys-stat-value"><?= number_format($usageStats['success_count'] ?? 0) ?></div>
                                    <div class="sys-stat-label">ناجحة</div>
                                </div>
                                <div class="sys-stat-box danger">
                                    <div class="sys-stat-value"><?= number_format($usageStats['fail_count'] ?? 0) ?></div>
                                    <div class="sys-stat-label">فاشلة</div>
                                </div>
                                <div class="sys-stat-box info">
                                    <div class="sys-stat-value">$<?= number_format($usageStats['total_cost'], 4) ?></div>
                                    <div class="sys-stat-label">التكلفة</div>
                                </div>
                            </div>

                            <!-- Usage bar -->
                            <div class="sys-usage-bar-wrapper">
                                <div class="sys-usage-bar-header">
                                    <span>الاستخدام الشهري</span>
                                    <span><?= $usageStats['total_requests'] ?> / <?= number_format($usageStats['monthly_limit']) ?></span>
                                </div>
                                <div class="sys-usage-bar">
                                    <div class="sys-usage-bar-fill <?= $usageStats['usage_percent'] > 80 ? 'warning' : '' ?> <?= $usageStats['usage_percent'] > 95 ? 'danger' : '' ?>"
                                         style="width: <?= min($usageStats['usage_percent'], 100) ?>%"></div>
                                </div>
                                <div class="sys-usage-bar-footer">
                                    <span>المتبقي: <?= number_format($usageStats['remaining']) ?> طلب</span>
                                    <span><?= $usageStats['usage_percent'] ?>%</span>
                                </div>
                            </div>

                            <div class="sys-form-grid" style="margin-top: 20px;">
                                <div class="sys-field">
                                    <label class="sys-label" for="gc_monthly_limit">
                                        <i class="fa fa-tachometer"></i> الحد الشهري (عدد الطلبات)
                                    </label>
                                    <input type="number" class="sys-input" id="gc_monthly_limit" name="gc_monthly_limit"
                                           value="<?= Html::encode($googleCloud['monthly_limit'] ?? '1000') ?>"
                                           min="0" step="100">
                                </div>
                                <div class="sys-field">
                                    <label class="sys-label" for="gc_cost_per_request">
                                        <i class="fa fa-usd"></i> التكلفة لكل طلب ($)
                                    </label>
                                    <input type="number" class="sys-input" id="gc_cost_per_request" name="gc_cost_per_request"
                                           value="<?= Html::encode($googleCloud['cost_per_request'] ?? '0.0015') ?>"
                                           min="0" step="0.0001">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="sys-form-actions">
                        <button type="submit" class="sys-save-btn">
                            <i class="fa fa-save"></i> حفظ الإعدادات
                        </button>
                        <button type="button" class="sys-cancel-btn" onclick="window.location.reload()">
                            <i class="fa fa-undo"></i> إلغاء التغييرات
                        </button>
                    </div>
                </form>
            </div>

            <!-- ═══════════ Google Maps Tab (نفس فكرة وتصميم تحليل الوثائق) ═══════════ -->
            <div class="sys-tab-content <?= $activeTab === 'google_maps' ? 'active' : '' ?>" id="tab-google_maps">
                <form method="post" action="<?= Url::to(['system-settings']) ?>">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <input type="hidden" name="settings_tab" value="google_maps">

                    <!-- حالة الاتصال / التكوين -->
                    <div class="sys-card sys-status-card">
                        <div class="sys-card-header">
                            <div class="sys-card-title">
                                <i class="fa fa-map-marker"></i> حالة التكوين
                            </div>
                        </div>
                        <div class="sys-card-body">
                            <div class="sys-connection-status" id="gm-connection-status">
                                <?php if (!empty($googleMaps['configured'])): ?>
                                    <div class="sys-status-indicator configured">
                                        <i class="fa fa-check-circle fa-2x"></i>
                                        <div>
                                            <strong>تم التكوين</strong>
                                            <p>مفتاح Google Maps API محفوظ — خريطة تتبع الموظفين ستستخدم خريطة Google تلقائياً</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="sys-status-indicator not-configured">
                                        <i class="fa fa-exclamation-circle fa-2x"></i>
                                        <div>
                                            <strong>غير مكوّن</strong>
                                            <p>أدخل مفتاح Google Maps API لتفعيل خريطة Google في صفحة تتبع الموظفين (معالم، محلات، مستشفيات)</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- مفتاح API -->
                    <div class="sys-card">
                        <div class="sys-card-body">
                            <div class="sys-form-group">
                                <label for="gm_api_key" class="sys-label">
                                    <i class="fa fa-key"></i> مفتاح Google Maps API
                                </label>
                                <input type="text"
                                       id="gm_api_key"
                                       name="gm_api_key"
                                       class="sys-input"
                                       placeholder="<?= !empty($googleMaps['configured']) ? 'اتركه فارغاً للإبقاء على المفتاح الحالي' : 'AIza...' ?>"
                                       value=""
                                       autocomplete="off">
                                <p class="sys-field-hint">احصل على المفتاح من <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console</a> — فعّل «Maps JavaScript API» ثم أنشئ مفتاح API.</p>
                            </div>
                        </div>
                    </div>

                    <!-- دليل مختصر (قابل للطي) -->
                    <div class="sys-card sys-guide-card">
                        <div class="sys-card-header sys-guide-toggle" onclick="toggleGmGuide()">
                            <div class="sys-card-title">
                                <i class="fa fa-graduation-cap"></i> كيف تحصل على المفتاح؟
                            </div>
                            <div class="sys-guide-toggle-hint">
                                <span id="gm-guide-toggle-text">عرض الدليل</span>
                                <i class="fa fa-chevron-down" id="gm-guide-chevron"></i>
                            </div>
                        </div>
                        <div class="sys-guide-body" id="gm-setup-guide" style="display:none;">
                            <div class="gc-step active">
                                <div class="gc-step-header">
                                    <div class="gc-step-number">1</div>
                                    <div>
                                        <h3>إنشاء مفتاح API</h3>
                                        <p>ادخل إلى <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">APIs & Services → Credentials</a></p>
                                    </div>
                                </div>
                                <div class="gc-step-instructions">
                                    <div class="gc-instruction-item"><span class="gc-inst-num">1</span><span>من القائمة اختر <strong>Create Credentials</strong> → <strong>API key</strong></span></div>
                                    <div class="gc-instruction-item"><span class="gc-inst-num">2</span><span>فعّل <strong>Maps JavaScript API</strong> من قسم Library إن لم يكن مفعّلاً</span></div>
                                    <div class="gc-instruction-item"><span class="gc-inst-num">3</span><span>انسخ المفتاح (يبدأ بـ <code>AIza</code>) والصقه في الحقل أعلاه</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- أزرار الحفظ -->
                    <div class="sys-card sys-actions-card">
                        <div class="sys-card-body">
                            <button type="submit" class="sys-save-btn">
                                <i class="fa fa-save"></i> حفظ الإعدادات
                            </button>
                            <button type="button" class="sys-cancel-btn" onclick="window.location.reload()">
                                <i class="fa fa-undo"></i> إلغاء التغييرات
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ═══════════ General Settings Tab ═══════════ -->
            <div class="sys-tab-content <?= $activeTab === 'general' ? 'active' : '' ?>" id="tab-general">

                <!-- Search -->
                <div class="gs-search-box">
                    <i class="fa fa-search"></i>
                    <input type="text" id="gs-search" class="gs-search-input" placeholder="ابحث في الإعدادات..." oninput="filterSettings(this.value)">
                </div>

                <!-- ── 1. إعدادات عامة ── -->
                <div class="gs-category" data-category="general">
                    <div class="gs-category-header">
                        <div class="gs-category-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fa fa-cog"></i>
                        </div>
                        <div>
                            <h3>إعدادات عامة</h3>
                            <p>الحالات وأنواع المستندات وطرق الدفع</p>
                        </div>
                    </div>
                    <div class="gs-items-grid">
                        <a href="<?= Url::to(['/status/status/index']) ?>" class="gs-item" data-search="الحالات status">
                            <div class="gs-item-icon"><i class="fa fa-toggle-on"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">الحالات</span>
                                <span class="gs-item-desc">إدارة حالات السجلات</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/documentStatus/document-status/index']) ?>" class="gs-item" data-search="حالات المستندات document status">
                            <div class="gs-item-icon"><i class="fa fa-file-o"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">حالات المستندات</span>
                                <span class="gs-item-desc">حالات الوثائق والملفات</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/documentType/document-type/index']) ?>" class="gs-item" data-search="أنواع المستندات document type">
                            <div class="gs-item-icon"><i class="fa fa-files-o"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">أنواع المستندات</span>
                                <span class="gs-item-desc">تصنيف أنواع الوثائق</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/paymentType/payment-type/index']) ?>" class="gs-item" data-search="طرق الدفع payment type">
                            <div class="gs-item-icon"><i class="fa fa-credit-card"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">طرق الدفع</span>
                                <span class="gs-item-desc">وسائل الدفع المتاحة</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/expenseCategories/expense-categories/index']) ?>" class="gs-item" data-search="فئات المصروفات expense categories">
                            <div class="gs-item-icon"><i class="fa fa-tags"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">فئات المصروفات</span>
                                <span class="gs-item-desc">تصنيف المصاريف</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/bancks/bancks/index']) ?>" class="gs-item" data-search="البنوك banks">
                            <div class="gs-item-icon"><i class="fa fa-university"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">البنوك</span>
                                <span class="gs-item-desc">المصارف وحساباتها</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                    </div>
                </div>

                <!-- ── 2. الموقع الجغرافي ── -->
                <div class="gs-category" data-category="geo">
                    <div class="gs-category-header">
                        <div class="gs-category-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="fa fa-globe"></i>
                        </div>
                        <div>
                            <h3>الموقع الجغرافي</h3>
                            <p>المدن والجنسيات</p>
                        </div>
                    </div>
                    <div class="gs-items-grid">
                        <a href="<?= Url::to(['/city/city/index']) ?>" class="gs-item" data-search="المدن city">
                            <div class="gs-item-icon"><i class="fa fa-map-marker"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">المدن</span>
                                <span class="gs-item-desc">قائمة المدن والمناطق</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/citizen/citizen/index']) ?>" class="gs-item" data-search="الجنسيات citizen nationality">
                            <div class="gs-item-icon"><i class="fa fa-flag"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">الجنسيات</span>
                                <span class="gs-item-desc">قائمة الجنسيات</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                    </div>
                </div>

                <!-- ── 3. إعدادات العملاء ── -->
                <div class="gs-category" data-category="customers">
                    <div class="gs-category-header">
                        <div class="gs-category-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fa fa-users"></i>
                        </div>
                        <div>
                            <h3>إعدادات العملاء</h3>
                            <p>بيانات التواصل والقرابة والمصادر</p>
                        </div>
                    </div>
                    <div class="gs-items-grid">
                        <a href="<?= Url::to(['/cousins/cousins/index']) ?>" class="gs-item" data-search="صلة القرابة cousins">
                            <div class="gs-item-icon"><i class="fa fa-sitemap"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">صلة القرابة</span>
                                <span class="gs-item-desc">أنواع العلاقات الأسرية</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/hearAboutUs/hear-about-us/index']) ?>" class="gs-item" data-search="مصدر التعرف علينا hear about us">
                            <div class="gs-item-icon"><i class="fa fa-bullhorn"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">مصدر التعرف علينا</span>
                                <span class="gs-item-desc">كيف عرفوا عنّا</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/feelings/feelings/index']) ?>" class="gs-item" data-search="المشاعر feelings">
                            <div class="gs-item-icon"><i class="fa fa-smile-o"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">المشاعر</span>
                                <span class="gs-item-desc">حالات العملاء</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/contactType/contact-type/index']) ?>" class="gs-item" data-search="أنواع الاتصال contact type">
                            <div class="gs-item-icon"><i class="fa fa-phone-square"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">أنواع الاتصال</span>
                                <span class="gs-item-desc">طرق التواصل مع العملاء</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/connectionResponse/connection-response/index']) ?>" class="gs-item" data-search="ردود الاتصال connection response">
                            <div class="gs-item-icon"><i class="fa fa-reply"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">ردود الاتصال</span>
                                <span class="gs-item-desc">نتائج محاولات الاتصال</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                    </div>
                </div>

                <!-- ── 4. القسم القضائي ── -->
                <div class="gs-category" data-category="legal">
                    <div class="gs-category-header">
                        <div class="gs-category-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <i class="fa fa-gavel"></i>
                        </div>
                        <div>
                            <h3>القسم القضائي</h3>
                            <p>الإجراءات والمحاكم والمحامون</p>
                        </div>
                    </div>
                    <div class="gs-items-grid">
                        <a href="<?= Url::to(['/judiciaryActions/judiciary-actions/index']) ?>" class="gs-item" data-search="الإجراءات القضائية judiciary actions">
                            <div class="gs-item-icon"><i class="fa fa-legal"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">الإجراءات القضائية</span>
                                <span class="gs-item-desc">أنواع الإجراءات القانونية</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/judiciaryType/judiciary-type/index']) ?>" class="gs-item" data-search="أنواع القضايا judiciary type">
                            <div class="gs-item-icon"><i class="fa fa-folder-open"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">أنواع القضايا</span>
                                <span class="gs-item-desc">تصنيفات القضايا</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/lawyers/lawyers/index']) ?>" class="gs-item" data-search="المحامون lawyers">
                            <div class="gs-item-icon"><i class="fa fa-briefcase"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">المحامون</span>
                                <span class="gs-item-desc">قائمة المحامين المعتمدين</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/court/court/index']) ?>" class="gs-item" data-search="المحاكم court">
                            <div class="gs-item-icon"><i class="fa fa-institution"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">المحاكم</span>
                                <span class="gs-item-desc">المحاكم والجهات القضائية</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/JudiciaryInformAddress/judiciary-inform-address/index']) ?>" class="gs-item" data-search="عناوين التبليغ inform address">
                            <div class="gs-item-icon"><i class="fa fa-map-signs"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">عناوين التبليغ</span>
                                <span class="gs-item-desc">عناوين التبليغ القضائي</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                    </div>
                </div>

                <!-- ── 5. الوظائف والرسائل ── -->
                <div class="gs-category" data-category="other">
                    <div class="gs-category-header">
                        <div class="gs-category-icon" style="background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);">
                            <i class="fa fa-briefcase"></i>
                        </div>
                        <div>
                            <h3>الوظائف والرسائل</h3>
                            <p>جهات العمل والمسميات الوظيفية والرسائل</p>
                        </div>
                    </div>
                    <div class="gs-items-grid">
                        <a href="<?= Url::to(['/jobs/jobs/index']) ?>" class="gs-item" data-search="جهات العمل jobs">
                            <div class="gs-item-icon"><i class="fa fa-building-o"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">جهات العمل</span>
                                <span class="gs-item-desc">الشركات وجهات التوظيف</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/designation/designation/index']) ?>" class="gs-item" data-search="المسميات الوظيفية designation">
                            <div class="gs-item-icon"><i class="fa fa-id-badge"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">المسميات الوظيفية</span>
                                <span class="gs-item-desc">الألقاب والمناصب</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                        <a href="<?= Url::to(['/sms/sms/index']) ?>" class="gs-item" data-search="الرسائل النصية sms">
                            <div class="gs-item-icon"><i class="fa fa-envelope"></i></div>
                            <div class="gs-item-text">
                                <span class="gs-item-label">الرسائل النصية</span>
                                <span class="gs-item-desc">قوالب وإعدادات SMS</span>
                            </div>
                            <i class="fa fa-chevron-left gs-item-arrow"></i>
                        </a>
                    </div>
                </div>

                <!-- No results message -->
                <div class="gs-no-results" id="gs-no-results" style="display:none;">
                    <i class="fa fa-search fa-3x"></i>
                    <h3>لا توجد نتائج</h3>
                    <p>لم يتم العثور على إعدادات تطابق بحثك</p>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
$testUrl = Url::to(['test-google-connection']);
$js = <<<JS
// دليل خريطة Google — طيّ/فتح
window.toggleGmGuide = function() {
    var body = $('#gm-setup-guide');
    var text = $('#gm-guide-toggle-text');
    var chevron = $('#gm-guide-chevron');
    if (body.is(':visible')) {
        body.slideUp(300);
        text.text('عرض الدليل');
        chevron.css('transform', 'rotate(0deg)');
    } else {
        body.slideDown(300);
        text.text('إخفاء الدليل');
        chevron.css('transform', 'rotate(180deg)');
    }
};

// Tab navigation (only for non-disabled)
$('.sys-nav-item:not(.disabled)').on('click', function(e) {
    var tab = $(this).data('tab');
    // إذا العنصر فيه data-tab → تبديل تبويب، وإلا → رابط عادي (مثل إدارة الصور)
    if (!tab) return; // اسمح للرابط يشتغل طبيعي
    e.preventDefault();
    $('.sys-nav-item').removeClass('active');
    $(this).addClass('active');
    $('.sys-tab-content').removeClass('active');
    $('#tab-' + tab).addClass('active');
});

// Clear textarea on focus if masked
$('#gc_private_key').on('focus', function() {
    if ($(this).val() === '••••••••••') {
        $(this).val('');
    }
});
$('#gc_private_key').on('blur', function() {
    if ($(this).val().trim() === '') {
        $(this).val('••••••••••');
    }
});

// ═══════════ General Settings Search ═══════════
window.filterSettings = function(query) {
    query = query.trim().toLowerCase();
    var hasResults = false;
    
    if (!query) {
        // Show all
        $('.gs-category').show();
        $('.gs-item').show();
        $('#gs-no-results').hide();
        return;
    }
    
    $('.gs-category').each(function() {
        var cat = $(this);
        var catVisible = false;
        
        cat.find('.gs-item').each(function() {
            var item = $(this);
            var searchText = (item.attr('data-search') || '').toLowerCase();
            var label = item.find('.gs-item-label').text().toLowerCase();
            var desc = item.find('.gs-item-desc').text().toLowerCase();
            var match = searchText.indexOf(query) !== -1 || label.indexOf(query) !== -1 || desc.indexOf(query) !== -1;
            
            item.toggle(match);
            if (match) catVisible = true;
        });
        
        cat.toggle(catVisible);
        if (catVisible) hasResults = true;
    });
    
    $('#gs-no-results').toggle(!hasResults);
};

// ═══════════ Setup Guide ═══════════

// Toggle guide visibility
window.toggleGuide = function() {
    var body = $('#setup-guide');
    var text = $('#guide-toggle-text');
    var chevron = $('#guide-chevron');
    if (body.is(':visible')) {
        body.slideUp(300);
        text.text('عرض الدليل');
        chevron.css('transform', 'rotate(0deg)');
    } else {
        body.slideDown(300);
        text.text('إخفاء الدليل');
        chevron.css('transform', 'rotate(180deg)');
    }
};

// Navigate between steps
window.goToStep = function(step) {
    $('.gc-step').removeClass('active');
    $('#gc-step-' + step).addClass('active');
    // Update progress dots
    $('.gc-step-dot').removeClass('active completed');
    for (var i = 1; i < step; i++) {
        $('.gc-step-dot[data-step="' + i + '"]').addClass('completed');
    }
    $('.gc-step-dot[data-step="' + step + '"]').addClass('active');
    // Scroll to top of guide
    $('#setup-guide')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
};

// Parse JSON credentials
window.parseJsonCredentials = function() {
    var jsonText = $('#gc-json-paste').val().trim();
    var resultDiv = $('#gc-parse-result');
    
    if (!jsonText) {
        resultDiv.html('<div class="gc-parse-error"><i class="fa fa-exclamation-circle"></i> الصق محتوى ملف JSON أولاً</div>').show();
        return;
    }
    
    try {
        var data = JSON.parse(jsonText);
        var filled = 0;
        
        if (data.project_id) {
            $('#gc_project_id').val(data.project_id).css('border-color', '#28a745');
            filled++;
        }
        if (data.client_email) {
            $('#gc_client_email').val(data.client_email).css('border-color', '#28a745');
            filled++;
        }
        if (data.private_key) {
            $('#gc_private_key').val(data.private_key).css('border-color', '#28a745');
            filled++;
        }
        
        resultDiv.html(
            '<div class="gc-parse-success">' +
            '<i class="fa fa-check-circle"></i> تم استخراج ' + filled + ' حقول بنجاح! تحقق من البيانات أدناه ثم اضغط "حفظ الإعدادات"' +
            '</div>'
        ).show();
        
        // Flash green on filled fields
        setTimeout(function() {
            $('#gc_project_id, #gc_client_email, #gc_private_key').css('border-color', '');
        }, 3000);
        
    } catch (e) {
        resultDiv.html('<div class="gc-parse-error"><i class="fa fa-exclamation-circle"></i> صيغة JSON غير صالحة — تأكد من نسخ المحتوى كاملاً</div>').show();
    }
};

// Copy JSON value to field
window.copyJsonValue = function(el, fieldId) {
    // This is for the mockup — just highlight the target field
    var field = $('#' + fieldId);
    field.css({ 'border-color': '#4285f4', 'box-shadow': '0 0 0 3px rgba(66,133,244,0.3)' });
    setTimeout(function() {
        field.css({ 'border-color': '', 'box-shadow': '' });
    }, 2000);
};

// Test connection
window.testGoogleConnection = function() {
    var btn = $('#btn-test-connection');
    var statusDiv = $('#connection-status');
    
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الاختبار...');
    
    $.ajax({
        url: '{$testUrl}',
        type: 'POST',
        dataType: 'json',
        data: { _csrf: $('meta[name="csrf-token"]').attr('content') || $('input[name="_csrf"]').val() },
        success: function(res) {
            if (res.success) {
                statusDiv.html(
                    '<div class="sys-status-indicator connected">' +
                    '  <i class="fa fa-check-circle fa-2x"></i>' +
                    '  <div><strong>متصل بنجاح!</strong>' +
                    '  <p>' + res.message + (res.project_id ? ' — المشروع: ' + res.project_id : '') + '</p></div>' +
                    '</div>'
                );
            } else {
                statusDiv.html(
                    '<div class="sys-status-indicator error">' +
                    '  <i class="fa fa-times-circle fa-2x"></i>' +
                    '  <div><strong>فشل الاتصال</strong>' +
                    '  <p>' + res.error + '</p></div>' +
                    '</div>'
                );
            }
        },
        error: function() {
            statusDiv.html(
                '<div class="sys-status-indicator error">' +
                '  <i class="fa fa-times-circle fa-2x"></i>' +
                '  <div><strong>خطأ في الشبكة</strong>' +
                '  <p>لم يتم الاتصال بالخادم</p></div>' +
                '</div>'
            );
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fa fa-plug"></i> اختبار الاتصال');
        }
    });
};
JS;
$this->registerJs($js);
?>
