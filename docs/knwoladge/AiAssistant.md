Siapa mengerjakan apa

  ┌───────────────────────┬──────────────────┬─────────────────────┐
  │         Tugas         │ Dikerjakan oleh  │        Beban        │    
  ├───────────────────────┼──────────────────┼─────────────────────┤
  │ Hitung omzet, laba,   │ MySQL + PHP      │ Sangat ringan,      │
  │ stok (akuntansi &     │ (query SUM/JOIN) │ milidetik           │    
  │ knowledge)            │                  │                     │    
  ├───────────────────────┼──────────────────┼─────────────────────┤    
  │ Forecasting           │ PHP (aritmatika  │ Ringan — cuma rumus │    
  │ (Holt-Winters,        │ biasa, looping   │  matematika, bukan  │    
  │ Croston)              │ array)           │ training model      │    
  ├───────────────────────┼──────────────────┼─────────────────────┤    
  │ Memahami pertanyaan & │ Gemini API (di   │ Nol beban di        │    
  │  menyusun jawaban     │ server Google)   │ server-mu — cuma    │    
  │                       │                  │ panggil API         │    
  └───────────────────────┴──────────────────┴─────────────────────┘    

  Kuncinya: Gemini tidak menghitung apa-apa. Dia cuma (1) baca
  pertanyaan, (2) pilih tool mana dipanggil, (3) ubah angka hasil PHP   
  jadi kalimat. Semua hitungan berat (statistik, akuntansi) dilakukan   
  PHP di server kamu — dan itu cuma operasi matematika biasa atas data  
  yang sudah ada, bukan melatih model AI. Holt-Winters & Croston itu    
  rumus puluhan baris, jalan dalam hitungan milidetik bahkan untuk      
  ribuan baris penjualan.

  Apakah Gemini 2.0 sanggup semua ini?

  Ya, sepenuhnya. Gemini 2.0 Flash:
  - ✅ Mendukung function calling (inti pendekatan kita) secara native. 
  - ✅ Paham Bahasa Indonesia dengan baik.
  - ✅ Cukup pintar untuk menjawab konsep akuntansi umum + menjelaskan  
  hasil forecasting.
  - ✅ Gratis di free tier (cukup untuk pemakaian toko: ratusan/ribuan  
  pesan per hari).

  Biaya & latensi nyata

  - Beban server-mu: hampir tidak bertambah — query DB ringan + 1–2     
  panggilan HTTP ke Gemini per pertanyaan.
  - Latensi: ~1–3 detik per jawaban (waktu tunggu API Gemini), bukan    
  karena hitungan berat.
  - Kuota: free tier Gemini 2.0 Flash (per Google AI Studio) lebih dari 
  cukup untuk 1 toko.

  Satu-satunya "biaya" sesungguhnya: waktu development untuk menulis    
  tools + rumus forecasting. Secara performa runtime, ini enteng.    