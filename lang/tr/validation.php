<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | APP_LOCALE and APP_FALLBACK_LOCALE are both "tr" in production, and
    | Laravel has no built-in Turkish translations — without this file every
    | validation error (login forms, booking forms, admin forms) rendered as
    | the raw untranslated key (e.g. "validation.required") instead of a
    | real message. Only the rules actually used by this app's FormRequest
    | classes are covered.
    |
    */

    'accepted' => ':attribute alanı kabul edilmelidir.',
    'active_url' => ':attribute geçerli bir adres değil.',
    'after' => ':attribute, :date tarihinden sonraki bir tarih olmalıdır.',
    'after_or_equal' => ':attribute, :date tarihiyle aynı veya sonraki bir tarih olmalıdır.',
    'alpha' => ':attribute sadece harflerden oluşmalıdır.',
    'alpha_dash' => ':attribute sadece harf, rakam, tire ve alt çizgiden oluşmalıdır.',
    'alpha_num' => ':attribute sadece harf ve rakamlardan oluşmalıdır.',
    'array' => ':attribute bir dizi olmalıdır.',
    'before' => ':attribute, :date tarihinden önceki bir tarih olmalıdır.',
    'before_or_equal' => ':attribute, :date tarihiyle aynı veya önceki bir tarih olmalıdır.',
    'between' => [
        'numeric' => ':attribute :min ile :max arasında olmalıdır.',
        'string' => ':attribute :min ile :max karakter arasında olmalıdır.',
        'array' => ':attribute :min ile :max öğe arasında olmalıdır.',
    ],
    'boolean' => ':attribute alanı doğru veya yanlış olmalıdır.',
    'confirmed' => ':attribute doğrulaması eşleşmiyor.',
    'date' => ':attribute geçerli bir tarih değil.',
    'date_format' => ':attribute, :format formatı ile eşleşmiyor.',
    'digits' => ':attribute :digits hane olmalıdır.',
    'email' => ':attribute geçerli bir e-posta adresi olmalıdır.',
    'exists' => 'Seçilen :attribute geçersiz.',
    'gt' => [
        'numeric' => ':attribute, :value değerinden büyük olmalıdır.',
        'string' => ':attribute, :value karakterden fazla olmalıdır.',
    ],
    'in' => 'Seçilen :attribute geçersiz.',
    'integer' => ':attribute tam sayı olmalıdır.',
    'lt' => [
        'numeric' => ':attribute, :value değerinden küçük olmalıdır.',
        'string' => ':attribute, :value karakterden az olmalıdır.',
    ],
    'max' => [
        'numeric' => ':attribute :max değerinden büyük olamaz.',
        'string' => ':attribute :max karakterden fazla olamaz.',
        'array' => ':attribute :max öğeden fazla olamaz.',
    ],
    'min' => [
        'numeric' => ':attribute en az :min olmalıdır.',
        'string' => ':attribute en az :min karakter olmalıdır.',
        'array' => ':attribute en az :min öğe içermelidir.',
    ],
    'numeric' => ':attribute bir sayı olmalıdır.',
    'required' => ':attribute alanı zorunludur.',
    'required_if' => ':other, :value olduğunda :attribute alanı zorunludur.',
    'string' => ':attribute bir metin olmalıdır.',
    'unique' => 'Bu :attribute daha önce kullanılmış.',

    /*
    |--------------------------------------------------------------------------
    | Custom Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'identifier' => 'telefon / referans kodu',
        'password' => 'şifre',
        'pin' => 'PIN',
        'email' => 'e-posta',
        'trainer_id' => 'eğitmen',
        'phone' => 'telefon',
        'ref_code' => 'referans kodu',
        'date' => 'tarih',
        'time' => 'saat',
        'horse_id' => 'at',
        'package_id' => 'paket',
        'safari_tour_id' => 'safari turu',
        'participants' => 'katılımcı sayısı',
        'note' => 'not',
        'notes' => 'not',
        'name' => 'isim',
        'status' => 'durum',
        'user_id' => 'üye',
        'student_user_id' => 'öğrenci',
        'reservation_id' => 'rezervasyon',
        'discipline_level' => 'disiplin seviyesi',
        'activity_label' => 'aktivite',
        'lesson_count' => 'ders sayısı',
        'price_try' => 'fiyat',
        'price_per_person' => 'kişi başı fiyat',
        'badge_label' => 'rozet etiketi',
        'is_active' => 'aktiflik durumu',
        'is_featured' => 'öne çıkarma durumu',
        'is_primary' => 'birincil durum',
        'cancellation_hours' => 'iptal süresi',
        'duration_minutes' => 'süre (dakika)',
        'description' => 'açıklama',
        'display_name' => 'görünen isim',
        'sort_order' => 'sıralama',
        'value' => 'değer',
        'delta' => 'tutar',
        'package_total' => 'paket toplamı',
    ],
];
