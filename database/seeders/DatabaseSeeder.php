<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Announcement;
use App\Models\CashTransaction;
use App\Models\ClassRoom;
use App\Models\Due;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = 'password';

        $admin = User::create([
            'name' => 'Rina Wulandari',
            'email' => 'admin@korlassync.test',
            'password' => $password,
            'role' => User::ROLE_ADMIN,
            'phone' => '0812 1000 2000',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $coordinatorRows = [
            ['Bima Prasetyo', 'koordinator@korlassync.test', '0812 3000 4000'],
            ['Sinta Maharani', 'sinta@korlassync.test', '0812 5555 6666'],
            ['Andi Kurniawan', 'andi@korlassync.test', '0812 7777 8888'],
            ['Dewi Anggraini', 'dewi@korlassync.test', '0812 1111 3333'],
            ['Rudi Hartono', 'rudi@korlassync.test', '0812 2222 4444'],
            ['Lina Marlina', 'lina@korlassync.test', '0812 9999 7777'],
        ];

        $coordinators = collect($coordinatorRows)->map(fn (array $row) => User::create([
            'name' => $row[0],
            'email' => $row[1],
            'password' => $password,
            'role' => User::ROLE_KOORDINATOR,
            'phone' => $row[2],
            'is_active' => true,
            'email_verified_at' => now(),
        ]));

        $parentRows = [
            ['Yuri Handoko', 'orangtua@korlassync.test', '0813 1111 2222'],
            ['Dian Saputra', 'dian@korlassync.test', '0813 3333 4444'],
            ['Maya Lestari', 'maya@korlassync.test', '0813 5555 6666'],
            ['Fajar Nugroho', 'fajar@korlassync.test', '0813 7777 8888'],
            ['Ratna Sari', 'ratna@korlassync.test', '0813 9999 0000'],
        ];

        $parents = collect($parentRows)->map(fn (array $row) => User::create([
            'name' => $row[0],
            'email' => $row[1],
            'password' => $password,
            'role' => User::ROLE_PARENT,
            'phone' => $row[2],
            'is_active' => true,
            'email_verified_at' => now(),
        ]));

        $classDescriptions = [
            'Kelas 1' => 'Kelas awal dengan program literasi dan pembiasaan belajar.',
            'Kelas 2' => 'Kelas dasar dengan fokus numerasi dan proyek kreatif.',
            'Kelas 3' => 'Kelas dengan program membaca bersama dan karya siswa.',
            'Kelas 4' => 'Kelas dengan proyek sains sederhana dan kerja kelompok.',
            'Kelas 5' => 'Kelas dengan program kepemimpinan siswa dan literasi digital.',
            'Kelas 6' => 'Kelas persiapan akhir dengan fokus evaluasi dan kegiatan perpisahan.',
        ];

        $classes = collect(ClassRoom::LEVELS)->values()->map(fn (string $name, int $index) => ClassRoom::create([
            'name' => $name,
            'academic_year' => '2026/2027',
            'coordinator_id' => $coordinators[$index]->id,
            'description' => $classDescriptions[$name],
        ]));

        $studentRows = [
            ['Nadia Putri Handoko', 'P', 0],
            ['Raka Pratama', 'L', 1],
            ['Alya Zahra', 'P', 2],
            ['Raka Aditya Handoko', 'L', 0],
            ['Salsabila Putri', 'P', 2],
            ['Naufal Hakim', 'L', 3],
            ['Fikri Ramadhan', 'L', 3],
            ['Laras Kinanti', 'P', 4],
            ['Bintang Adi', 'L', 2],
            ['Citra Ayu Lestari', 'P', 4],
            ['Dimas Prakoso', 'L', 1],
            ['Safira Nabila', 'P', 0],
            ['Yoga Pratama', 'L', 1],
            ['Kirana Maharani', 'P', 4],
            ['Bagas Saputra', 'L', 3],
            ['Maya Anggraini', 'P', 2],
            ['Arif Setiawan', 'L', 0],
            ['Zaki Pratama', 'L', 1],
        ];

        $students = collect();

        foreach ($classes as $classIndex => $class) {
            foreach (array_slice($studentRows, $classIndex * 3, 3) as $studentIndex => [$name, $gender, $parentIndex]) {
                $students->push(Student::create([
                    'class_id' => $class->id,
                    'parent_id' => $parents[$parentIndex]->id,
                    'name' => $name,
                    'gender' => $gender,
                    'nis' => sprintf('26%d%03d', $classIndex + 1, $studentIndex + 1),
                    'birth_date' => Carbon::create(2014 + $classIndex, ($studentIndex % 12) + 1, ($studentIndex * 7) + 3),
                ]));
            }
        }

        $dues = [];

        foreach ($classes as $index => $class) {
            $amount = 50000 - ($index * 3000);

            $dues[$index] = [
                'november' => Due::create([
                    'class_id' => $class->id,
                    'created_by' => $coordinators[$index]->id,
                    'title' => 'Kas Kelas November',
                    'description' => 'Iuran rutin bulanan untuk kebutuhan operasional kelas.',
                    'amount' => $amount,
                    'period' => 'November 2026',
                    'due_date' => now()->addDays(4 + $index),
                    'is_active' => true,
                ]),
                'october' => Due::create([
                    'class_id' => $class->id,
                    'created_by' => $coordinators[$index]->id,
                    'title' => 'Kas Kelas Oktober',
                    'description' => 'Iuran rutin bulan Oktober.',
                    'amount' => $amount,
                    'period' => 'Oktober 2026',
                    'due_date' => now()->subDays(27),
                    'is_active' => true,
                ]),
                'semester' => Due::create([
                    'class_id' => $class->id,
                    'created_by' => $coordinators[$index]->id,
                    'title' => 'Dana Kegiatan Semester',
                    'description' => 'Dana bersama untuk kegiatan kelas, dekorasi, dan acara semester.',
                    'amount' => $amount * 3,
                    'period' => 'Semester Ganjil',
                    'due_date' => now()->addDays(24 + $index),
                    'is_active' => true,
                ]),
            ];
        }

        $recordPayment = function (
            Student $student,
            Due $due,
            string $status,
            Carbon $paidAt,
            string $method = 'transfer',
            ?string $note = null
        ) use ($admin): Payment {
            $coordinatorId = $due->classRoom->coordinator_id ?? $admin->id;

            $payment = Payment::create([
                'due_id' => $due->id,
                'student_id' => $student->id,
                'amount' => $due->amount,
                'paid_at' => $paidAt->toDateString(),
                'method' => $method,
                'status' => $status,
                'note' => $note,
                'verified_by' => $status === 'verified' ? $coordinatorId : null,
                'verified_at' => $status === 'verified' ? $paidAt->copy()->addHours(4) : null,
            ]);

            if ($status === 'verified') {
                CashTransaction::create([
                    'class_id' => $due->class_id,
                    'payment_id' => $payment->id,
                    'recorded_by' => $coordinatorId,
                    'type' => 'masuk',
                    'category' => 'Iuran',
                    'amount' => $payment->amount,
                    'description' => 'Iuran '.$due->title.' — '.$student->name,
                    'transaction_date' => $paidAt->toDateString(),
                ]);
            }

            return $payment;
        };

        foreach ($classes as $index => $class) {
            $classStudents = $students->where('class_id', $class->id)->values();

            $recordPayment($classStudents[0], $dues[$index]['october'], 'verified', now()->subDays(30 + $index));
            $recordPayment($classStudents[1], $dues[$index]['october'], 'verified', now()->subDays(29 + $index), 'qris');
            $recordPayment($classStudents[0], $dues[$index]['november'], 'verified', now()->subDays(2 + $index));
            $recordPayment($classStudents[1], $dues[$index]['november'], 'verified', now()->subDays(1 + $index));
            $recordPayment($classStudents[2], $dues[$index]['semester'], 'verified', now()->subDays(4 + $index));
        }

        $firstClassStudents = $students->where('class_id', $classes[0]->id)->values();
        $recordPayment(
            $firstClassStudents[2],
            $dues[0]['november'],
            'pending',
            now()->subHours(5),
            'transfer',
            'Transfer dari BCA, mohon diverifikasi.'
        );

        $secondClassStudents = $students->where('class_id', $classes[1]->id)->values();
        $recordPayment(
            $secondClassStudents[2],
            $dues[1]['november'],
            'rejected',
            now()->subDays(3),
            'qris',
            'Bukti pembayaran belum terbaca jelas.'
        );

        foreach ($classes as $index => $class) {
            CashTransaction::create([
                'class_id' => $class->id,
                'recorded_by' => $coordinators[$index]->id,
                'type' => 'masuk',
                'category' => 'Saldo awal',
                'amount' => 300000 + ($index * 75000),
                'description' => 'Saldo awal kas '.$class->name.' hasil kesepakatan orang tua.',
                'transaction_date' => now()->subMonths(5)->startOfMonth()->addDays(2)->toDateString(),
            ]);

            for ($month = 5; $month >= 1; $month--) {
                CashTransaction::create([
                    'class_id' => $class->id,
                    'recorded_by' => $coordinators[$index]->id,
                    'type' => 'masuk',
                    'category' => 'Donasi',
                    'amount' => 90000 + ((($index + $month) % 3) * 25000),
                    'description' => 'Donasi kegiatan kelas bulan '.now()->subMonths($month)->translatedFormat('F').'.',
                    'transaction_date' => now()->subMonths($month)->startOfMonth()->addDays(12)->toDateString(),
                ]);

                if ($month % 2 === 1) {
                    CashTransaction::create([
                        'class_id' => $class->id,
                        'recorded_by' => $coordinators[$index]->id,
                        'type' => 'keluar',
                        'category' => 'Operasional',
                        'amount' => 40000 + ($index * 5000),
                        'description' => 'Peralatan dan kebutuhan kelas bulan '.now()->subMonths($month)->translatedFormat('F').'.',
                        'transaction_date' => now()->subMonths($month)->startOfMonth()->addDays(18)->toDateString(),
                    ]);
                }
            }
        }

        foreach ($classes as $index => $class) {
            Agenda::create([
                'class_id' => $class->id,
                'created_by' => $coordinators[$index]->id,
                'title' => 'Rapat koordinasi orang tua '.$class->name,
                'description' => 'Membahas perkembangan kelas, rencana kegiatan, dan informasi penting lainnya.',
                'location' => 'Ruang '.$class->name,
                'starts_at' => now()->addDays($index + 3)->setTime(19, 0),
                'ends_at' => now()->addDays($index + 3)->setTime(21, 0),
                'is_published' => true,
            ]);

            Agenda::create([
                'class_id' => $class->id,
                'created_by' => $coordinators[$index]->id,
                'title' => 'Kerja bakti '.$class->name,
                'description' => 'Membersihkan kelas dan menata ulang papan informasi. Bawa alat kebersihan dari rumah.',
                'location' => 'Area '.$class->name,
                'starts_at' => now()->addDays($index + 10)->setTime(8, 0),
                'ends_at' => now()->addDays($index + 10)->setTime(10, 0),
                'is_published' => true,
            ]);
        }

        Agenda::create([
            'class_id' => null,
            'created_by' => $admin->id,
            'title' => 'Rapat pleno komite sekolah',
            'description' => 'Rapat bersama seluruh orang tua membahas program semester dan transparansi kas.',
            'location' => 'Aula utama sekolah',
            'starts_at' => now()->addDays(7)->setTime(9, 0),
            'ends_at' => now()->addDays(7)->setTime(11, 0),
            'is_published' => true,
        ]);

        Agenda::create([
            'class_id' => null,
            'created_by' => $admin->id,
            'title' => 'Libur semester ganjil',
            'description' => 'Kegiatan belajar diliburkan. Kelas kembali aktif sesuai kalender akademik.',
            'location' => 'Seluruh kelas',
            'starts_at' => now()->addDays(45)->setTime(8, 0),
            'ends_at' => now()->addDays(45)->setTime(8, 0),
            'is_published' => true,
        ]);

        foreach ($classes as $index => $class) {
            Announcement::create([
                'class_id' => $class->id,
                'created_by' => $coordinators[$index]->id,
                'title' => 'Informasi iuran '.$class->name,
                'body' => "Halo orang tua {$class->name},\n\nKas kelas dan pencatatan iuran dikelola oleh koordinator kelas. Silakan hubungi koordinator jika ada pertanyaan mengenai iuran atau kegiatan kelas.\n\nTerima kasih atas kerja samanya.",
                'priority' => $index === 0 ? 'penting' : 'normal',
                'is_pinned' => $index === 0,
                'published_at' => now()->subHours($index + 1),
            ]);
        }

        Announcement::create([
            'class_id' => null,
            'created_by' => $admin->id,
            'title' => 'Pembaruan jadwal libur semester',
            'body' => "Berdasarkan rapat komite sekolah, libur semester dimajukan satu minggu.\n\nKalender lengkap dapat dilihat pada menu Agenda.",
            'priority' => 'penting',
            'is_pinned' => true,
            'published_at' => now()->subHours(9),
        ]);

        Announcement::create([
            'class_id' => null,
            'created_by' => $admin->id,
            'title' => 'Panduan penggunaan KorlasSync',
            'body' => 'KorlasSync membantu orang tua memantau agenda dan pemberitahuan kelas. Gunakan menu Profil untuk memperbarui nomor telepon Anda.',
            'priority' => 'normal',
            'is_pinned' => false,
            'published_at' => now()->subDays(3),
        ]);

        Announcement::create([
            'class_id' => null,
            'created_by' => $admin->id,
            'title' => 'Draf program semester (internal)',
            'body' => 'Draf ini belum diterbitkan dan hanya terlihat oleh administrator.',
            'priority' => 'normal',
            'is_pinned' => false,
            'published_at' => null,
        ]);
    }
}
