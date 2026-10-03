# تحديثات Kontentainment Charts — v3.3.79

**التاريخ:** 4 أكتوبر 2026

## إضافة Soundcharts إلى Import Center

أصبح Soundcharts مصدر استيراد مستقلًا داخل **Charts → Import Center**. يتيح الاستيراد المباشر للـcharts المتاحة في حساب Soundcharts من دون رفع ملف CSV.

### ما الذي يشمله التحديث؟

- اختيار السوق، ونوع المحتوى: أغاني أو ألبومات.
- تحميل المنصات وقائمة الـcharts المتاحة من Soundcharts، ثم اختيار الـchart المطلوب.
- استيراد أحدث ترتيب متاح، بما يشمل المراكز، والتغير عن المركز السابق، وبيانات القياس وصورة الغلاف عند توفرها.
- مطابقة الأغاني مع الفنانين والمسارات الموجودة، وإنشاء السجلات الناقصة. يدعم الاستيراد الألبومات أيضًا.
- ربط النتائج بـChart Profile متوافق مع نوع المحتوى والمنصة والسوق وتكرار الـchart، لتظهر البيانات داخل الـchart المختار.
- حفظ سجل عملية الاستيراد وتحديث بيانات الـchart المستخدمة في الواجهة.
- جلب رمز OAuth من الخادم وتخزينه مؤقتًا؛ لا تُرسل بيانات الدخول إلى المتصفح.

## الإعداد والاستخدام

1. من **Charts → Settings → Service Nexus**، أدخل **Soundcharts Client ID** و**Client Secret**. أدخل **Team ID** فقط إذا كان حسابك يحتاجه.
2. تأكد أن خطة Soundcharts وبيانات الاعتماد تسمح بالوصول إلى واجهات الـcharts المطلوبة.
3. افتح **Charts → Import Center** واختر Soundcharts.
4. اختر السوق ونوع المحتوى والمنصة، ثم اضغط **Load Charts**.
5. اختر الـchart المصدر وChart Profile وجهة متوافقة، ثم نفّذ الاستيراد.

هذا الإصدار يستورد **أحدث ranking** الذي توفره واجهة Soundcharts للـchart المختار؛ لا يجلب أرشيفًا تاريخيًا كاملًا من Soundcharts.

## مراجع Soundcharts

- [المصادقة باستخدام OAuth](https://developers.soundcharts.com/api/authorization)
- [مرجع واجهات Charts](https://developers.soundcharts.com/api/reference/charts/summary)
