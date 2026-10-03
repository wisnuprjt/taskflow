# Use Case Diagram - TaskFlow

Diagram ini hanya memuat fitur yang **sudah diimplementasikan** di kode (backend Laravel dan frontend Next.js). Fitur yang belum ada atau ditunda dicatat terpisah di [bagian akhir](#tidak-dimasukkan-ke-diagram).

![Use Case Diagram TaskFlow](diagrams/use-case.svg)

Sumber diagram: [diagrams/use-case.puml](diagrams/use-case.puml) (PlantUML). Cara merender ada di [bagian Render](#render-diagram).

## Aktor

| Aktor | Deskripsi | Bukti di kode |
|---|---|---|
| **Member** | Pengguna terdaftar dengan role `member` (default). Bisa melihat semua task, tapi hanya boleh mengubah task yang **dibuatnya** atau yang **ditugaskan** kepadanya, dan hanya boleh menghapus task yang **dibuatnya**. | [`UserRole`](../backend/app/Enums/UserRole.php), migration `users.role` default `member`, [`TaskPolicy`](../backend/app/Policies/TaskPolicy.php) |
| **Admin** | Pengguna dengan role `admin`. **Mewarisi semua use case Member** (generalisasi), ditambah hak mengubah, menghapus, dan mengelola lampiran **semua task**. | `User::isAdmin()` di [`User.php`](../backend/app/Models/User.php), `TaskPolicy::update()` / `delete()` |

Tidak ada aktor tamu (guest). Aplikasi tidak punya fitur publik, dan semua halaman selain `/login` diarahkan ke login ([`(app)/layout.tsx`](../frontend/src/app/(app)/layout.tsx)).

**Prasyarat umum:** semua use case selain **UC-01 Login** mensyaratkan pengguna sudah login. Endpoint dilindungi middleware `auth:api` (JWT) di [`routes/api.php`](../backend/routes/api.php). Prasyarat ini sengaja tidak digambar sebagai `<<include>>` karena login adalah prasyarat, bukan bagian dari use case lain, dan agar diagram tidak terlalu padat.

**Pembuat task dan assignee bukan aktor terpisah.** Di sistem hanya ada dua role (`admin`, `member`). "Pembuat" dan "assignee" adalah kolom `tasks.created_by` dan `tasks.assigned_user_id`, jadi batasan itu dituliskan langsung di nama use case (misalnya "Mengubah Task Sendiri / yang Ditugaskan").

## Tabel use case

Semua endpoint diawali `/api`.

### Autentikasi

| ID | Nama | Aktor | Deskripsi singkat | Referensi |
|---|---|---|---|---|
| UC-01 | Login | Member, Admin | Masuk dengan email dan password, lalu menerima token JWT. Gagal dengan pesan error jika kredensial salah. Dibatasi 10 percobaan per menit. | `POST /auth/login`, `AuthController@login`, [`login/page.tsx`](../frontend/src/app/login/page.tsx) |
| UC-02 | Logout | Member, Admin | Keluar dari aplikasi. Token di-blacklist di server dan dihapus dari browser. | `POST /auth/logout`, `AuthController@logout`, [`(app)/layout.tsx`](../frontend/src/app/(app)/layout.tsx) |
| UC-03 | Melihat Info Akun | Member, Admin | Menampilkan nama dan role pengguna di header, sekaligus memulihkan sesi saat halaman dibuka ulang. | `GET /auth/me`, `AuthController@me`, [`AuthProvider.tsx`](../frontend/src/components/providers/AuthProvider.tsx) |

### Task

| ID | Nama | Aktor | Deskripsi singkat | Referensi |
|---|---|---|---|---|
| UC-04 | Melihat Daftar Task | Member, Admin | Menampilkan semua task dengan pagination (10 per halaman di UI). Tampil sebagai tabel di desktop dan kartu di mobile. | `GET /tasks`, `TaskController@index`, [`tasks/page.tsx`](../frontend/src/app/(app)/tasks/page.tsx) |
| UC-05 | Mencari Task | Member, Admin | *Extend* UC-04. Mencari berdasarkan judul task (dengan debounce). | `GET /tasks?search=`, `Task::scopeFilter()`, [`TaskFilters.tsx`](../frontend/src/components/tasks/TaskFilters.tsx) |
| UC-06 | Memfilter Task (status, priority) | Member, Admin | *Extend* UC-04. Menyaring daftar berdasarkan status dan/atau priority. | `GET /tasks?status=&priority=`, [`IndexTaskRequest`](../backend/app/Http/Requests/IndexTaskRequest.php) |
| UC-07 | Mengurutkan Task | Member, Admin | *Extend* UC-04. Mengurutkan berdasarkan tanggal dibuat, due date, priority (low < medium < high), atau judul. | `GET /tasks?sort=`, `Task::SORTABLE` |
| UC-08 | Melihat Detail Task | Member, Admin | Menampilkan detail task: deskripsi, status, priority, assignee, pembuat, dan tanggal. *Include* UC-15. | `GET /tasks/{id}`, `TaskController@show`, [`tasks/[id]/page.tsx`](../frontend/src/app/(app)/tasks/[id]/page.tsx) |
| UC-09 | Membuat Task | Member, Admin | Membuat task baru. Hanya judul yang wajib. Pengguna yang membuat otomatis tercatat sebagai pembuat. | `POST /tasks`, `TaskController@store`, [`StoreTaskRequest`](../backend/app/Http/Requests/StoreTaskRequest.php), [`TaskFormModal.tsx`](../frontend/src/components/tasks/TaskFormModal.tsx) |
| UC-10 | Menugaskan Task ke Pengguna | Member, Admin | *Extend* UC-09, UC-11, UC-12. Memilih assignee dari daftar pengguna (opsional). | `GET /users`, `UserController@index`, field `assigned_user_id` |
| UC-11 | Mengubah Task Sendiri / yang Ditugaskan | Member | Mengubah judul, deskripsi, status, priority, assignee, atau due date pada task yang dibuat oleh atau ditugaskan kepada pengguna. Selain itu ditolak (403) dan tombol Edit disembunyikan. | `PUT /tasks/{id}`, `TaskPolicy::update()`, [`UpdateTaskRequest`](../backend/app/Http/Requests/UpdateTaskRequest.php) |
| UC-12 | Mengubah Task Siapa Pun | Admin | Sama dengan UC-11, tapi berlaku untuk semua task. | `PUT /tasks/{id}`, `TaskPolicy::update()` (`isAdmin()`) |
| UC-13 | Menghapus Task Sendiri | Member | Menghapus task yang dibuat sendiri setelah konfirmasi. Lampiran (termasuk file fisiknya) dan komentar ikut terhapus. Assignee **tidak** boleh menghapus. | `DELETE /tasks/{id}`, `TaskPolicy::delete()`, `Task::booted()`, [`DeleteTaskDialog.tsx`](../frontend/src/components/tasks/DeleteTaskDialog.tsx) |
| UC-14 | Menghapus Task Siapa Pun | Admin | Sama dengan UC-13, tapi berlaku untuk semua task. | `DELETE /tasks/{id}`, `TaskPolicy::delete()` (`isAdmin()`) |

### Lampiran

| ID | Nama | Aktor | Deskripsi singkat | Referensi |
|---|---|---|---|---|
| UC-15 | Melihat Lampiran (dengan pratinjau) | Member, Admin | Dipanggil oleh UC-08. Menampilkan daftar lampiran task dengan nama, ukuran, dan tanggal. File gambar tampil sebagai pratinjau kecil, dan file lain sebagai label tipe (PDF, DOC, dst). | data lampiran di `GET /tasks/{id}`, [`AttachmentList.tsx`](../frontend/src/components/tasks/AttachmentList.tsx), [`AttachmentThumb.tsx`](../frontend/src/components/tasks/AttachmentThumb.tsx) |
| UC-16 | Mengunduh Lampiran | Member, Admin | Mengunduh file dengan nama aslinya. File disimpan di private storage, jadi hanya bisa diunduh dengan token. | `GET /attachments/{id}/download`, `TaskAttachmentController@download` |
| UC-17 | Mengelola Lampiran Task Sendiri / yang Ditugaskan (unggah, hapus) | Member | Mengunggah file (drag-and-drop atau pilih file, dengan progress bar) dan menghapus lampiran pada task yang dibuat oleh atau ditugaskan kepada pengguna. Mengunggah selalu *include* UC-19. | `POST /tasks/{id}/attachments`, `DELETE /attachments/{id}`, `TaskPolicy::update()`, [`AttachmentUploader.tsx`](../frontend/src/components/tasks/AttachmentUploader.tsx) |
| UC-18 | Mengelola Lampiran Task Siapa Pun (unggah, hapus) | Admin | Sama dengan UC-17, tapi berlaku untuk semua task. | endpoint yang sama, `TaskPolicy::update()` (`isAdmin()`) |
| UC-19 | Memvalidasi File (tipe, maks 20 MB) | (sistem) | Dipanggil oleh UC-17 dan UC-18. Hanya menerima jpg, jpeg, png, webp, pdf, doc, docx, xlsx, txt, mp4, webm, dengan ukuran maksimal 20 MB. Dicek di browser untuk umpan balik cepat dan di server sebagai penentu akhir (422). | [`StoreAttachmentRequest`](../backend/app/Http/Requests/StoreAttachmentRequest.php), `validate()` di [`AttachmentUploader.tsx`](../frontend/src/components/tasks/AttachmentUploader.tsx) |

### Komentar

| ID | Nama | Aktor | Deskripsi singkat | Referensi |
|---|---|---|---|---|
| UC-20 | Melihat Komentar | Member, Admin | Menampilkan komentar sebuah task secara kronologis, lengkap dengan nama penulis dan waktunya. | `GET /tasks/{id}/comments`, `TaskCommentController@index`, [`CommentSection.tsx`](../frontend/src/components/tasks/CommentSection.tsx) |
| UC-21 | Menambah Komentar | Member, Admin | Menulis komentar pada task mana pun (maks 2000 karakter). | `POST /tasks/{id}/comments`, `TaskCommentController@store`, [`StoreCommentRequest`](../backend/app/Http/Requests/StoreCommentRequest.php) |

## Relasi antar use case

| Relasi | Dari → Ke | Alasan |
|---|---|---|
| `<<extend>>` | UC-05, UC-06, UC-07 → UC-04 | Mencari, memfilter, dan mengurutkan bersifat opsional saat melihat daftar task. |
| `<<extend>>` | UC-10 → UC-09, UC-11, UC-12 | Memilih assignee opsional, baik saat membuat maupun mengubah task. |
| `<<include>>` | UC-08 → UC-15 | Detail task selalu memuat daftar lampirannya. |
| `<<include>>` | UC-17, UC-18 → UC-19 | Setiap unggahan selalu divalidasi. |
| Generalisasi aktor | Admin → Member | Admin bisa melakukan semua yang bisa dilakukan Member. |

## Matriks hak akses

Ringkasan dari [`TaskPolicy`](../backend/app/Policies/TaskPolicy.php). API menegakkan aturan ini (403), sedangkan UI hanya menyembunyikan tombol berdasarkan field `can.update` / `can.delete` di [`TaskResource`](../backend/app/Http/Resources/TaskResource.php).

| Aksi | Member (task orang lain) | Member (assignee) | Member (pembuat) | Admin |
|---|:-:|:-:|:-:|:-:|
| Melihat task, lampiran, komentar | ✅ | ✅ | ✅ | ✅ |
| Mengunduh lampiran, menambah komentar | ✅ | ✅ | ✅ | ✅ |
| Mengubah task | ❌ | ✅ | ✅ | ✅ |
| Mengunggah / menghapus lampiran | ❌ | ✅ | ✅ | ✅ |
| Menghapus task | ❌ | ❌ | ✅ | ✅ |

## Tidak dimasukkan ke diagram

### Tersedia di API, belum ada di UI

| Fitur | Keterangan |
|---|---|
| Filter berdasarkan assignee | `GET /tasks?assigned_user_id={id}` sudah didukung backend ([`IndexTaskRequest`](../backend/app/Http/Requests/IndexTaskRequest.php), `Task::scopeFilter()`), tapi halaman `/tasks` belum punya dropdown Assignee. Filter ini hanya bisa dipakai lewat Postman atau curl. |
| Jumlah data per halaman | `per_page` (1–50) bisa diatur lewat API, sedangkan UI selalu memakai 10. |

### Belum diimplementasikan

| Fitur | Keterangan |
|---|---|
| Registrasi akun | Tidak ada endpoint maupun halaman. Akun dibuat lewat seeder. |
| Lupa / reset password | Tabel `password_reset_tokens` bawaan Laravel ada, tapi tidak ada route atau halamannya. |
| Manajemen pengguna dan role | Tidak ada CRUD user. `GET /users` hanya dipakai untuk dropdown assignee. |
| Mengubah / menghapus komentar | Komentar hanya bisa dibuat dan dilihat (`task_comments` tidak punya `updated_at`). |

### Ditunda (lihat [architecture.md](architecture.md#deferred-features))

- Real-time: WebSocket/SSE, status online, indikator mengetik
- Queue job: email saat task ditugaskan, bulk update status, ekspor CSV/PDF
- Thumbnail di sisi server, simulasi virus scan, chunked upload (> 50 MB), versioning file
- Video streaming dan adaptive streaming
- Caching (Redis)

## Render diagram

PlantUML belum terpasang di mesin ini (tidak ada `plantuml`, `plantuml.jar`, atau Docker), jadi `use-case.svg` / `.png` belum dibuat. Pilih salah satu cara:

1. **VS Code (paling mudah):** pasang extension **PlantUML** (jebbs.plantuml), buka `use-case.puml`, tekan `Alt+D` untuk pratinjau, lalu klik kanan → **Export Current Diagram** → pilih `svg` dan `png`. Extension ini membutuhkan Java (sudah ada: Java 8) dan Graphviz, atau bisa diatur ke server render PlantUML.
2. **Tanpa instalasi:** tempel isi `use-case.puml` ke [plantuml.com/plantuml](https://www.plantuml.com/plantuml/uml) atau [PlantText](https://www.planttext.com), lalu unduh SVG/PNG-nya.
3. **Command line:** unduh `plantuml.jar` dari [plantuml.com/download](https://plantuml.com/download), lalu jalankan:
   ```bash
   java -jar plantuml.jar -tsvg -tpng documentation/diagrams/use-case.puml
   ```

Simpan hasilnya sebagai `documentation/diagrams/use-case.svg` dan `use-case.png` agar gambar di atas tampil.
