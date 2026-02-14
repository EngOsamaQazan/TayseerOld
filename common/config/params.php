<?php
return [
    /**
     * قاعدة روابط صور العملاء (ImageManager).
     * إذا مُعرّف: تُحمّل كل الصور من هذا العنوان (مثلاً من سيرفر جادل).
     * الطريقة القديمة كانت: https://jadal.aqssat.co/images/imagemanager/{id}_{fileHash}.{ext}
     * على نماء يمكن تعيين: 'customerImagesBaseUrl' => 'https://jadal.aqssat.co' في params-local
     */
    'customerImagesBaseUrl' => null,
];
