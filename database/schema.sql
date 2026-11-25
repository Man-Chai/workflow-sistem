-- =============================================================================
-- KORLASSYNC & WHATSAPP BOT INTEGRATION - DATABASE SCHEMA (PostgreSQL 16+)
-- =============================================================================
-- Script ini memuat pembuatan database, ekstensi, tabel, relasi (foreign keys),
-- fungsi trigger (sinkronisasi pembukuan <-> laporan keuangan, kalkulasi saldo,
-- enkripsi password bcrypt, dan webhook reminder agenda ke n8n), serta seed data
-- awal untuk level dan role.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. PEMBUATAN DATABASE (Opsional: Jalankan bagian ini jika DB belum dibuat)
-- -----------------------------------------------------------------------------
-- CREATE DATABASE n8n_chat_memory
--     WITH
--     OWNER = n8n
--     ENCODING = 'UTF8'
--     TEMPLATE = template0;
--
-- \c n8n_chat_memory;

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

-- -----------------------------------------------------------------------------
-- 2. EKSTENSI POSTGRESQL
-- -----------------------------------------------------------------------------
-- pgcrypto digunakan untuk generate UUID (gen_random_uuid) & hashing bcrypt (crypt/gen_salt)
CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;

-- pg_net (opsional) digunakan oleh trigger database untuk memanggil webhook HTTP ke n8n
DO $$
BEGIN
    CREATE EXTENSION IF NOT EXISTS pg_net WITH SCHEMA public;
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE 'Ekstensi pg_net tidak tersedia di server ini, melewati instalasi pg_net.';
END
$$;

-- -----------------------------------------------------------------------------
-- 3. PEMBUATAN TABEL UTAMA WEBSITE & BOT WA
-- -----------------------------------------------------------------------------

-- Tabel: level (Level Akses Pengguna & Bot WA)
CREATE TABLE IF NOT EXISTS public.level (
    id_level uuid DEFAULT gen_random_uuid() NOT NULL,
    nama character varying(100) NOT NULL,
    keterangan text,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT level_pkey PRIMARY KEY (id_level),
    CONSTRAINT level_nama_key UNIQUE (nama)
);

-- Tabel: role (Peran Pengguna: admin/operator, korlas/pengurus, user/undefined)
CREATE TABLE IF NOT EXISTS public.role (
    id_role uuid DEFAULT gen_random_uuid() NOT NULL,
    id_level uuid,
    nama character varying(100) NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT role_pkey PRIMARY KEY (id_role),
    CONSTRAINT role_nama_key UNIQUE (nama),
    CONSTRAINT role_id_level_fkey FOREIGN KEY (id_level)
        REFERENCES public.level(id_level) ON UPDATE CASCADE ON DELETE RESTRICT
);

-- Tabel: user (Akun Pengguna Website & Identitas WhatsApp / LID)
CREATE TABLE IF NOT EXISTS public."user" (
    id_user uuid DEFAULT gen_random_uuid() NOT NULL,
    id_role uuid,
    nama character varying(100) NOT NULL,
    telepon character varying(30),
    lid character varying(30),
    email character varying(100),
    password character varying(255) DEFAULT '$2y$12$aFptB6T/RFrl11yxr/rhPOKJM.jaUFJoBagwy/dJj1PiTxH8ndq.2'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT user_pkey PRIMARY KEY (id_user),
    CONSTRAINT user_email_key UNIQUE (email),
    CONSTRAINT user_telepon_key UNIQUE (telepon),
    CONSTRAINT user_lid_key UNIQUE (lid),
    CONSTRAINT user_id_role_fkey FOREIGN KEY (id_role)
        REFERENCES public.role(id_role) ON UPDATE CASCADE ON DELETE RESTRICT
);

-- Tabel: kelas (Data Kelas)
CREATE TABLE IF NOT EXISTS public.kelas (
    id_kelas uuid DEFAULT gen_random_uuid() NOT NULL,
    nama character varying(100) NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT kelas_pkey PRIMARY KEY (id_kelas),
    CONSTRAINT kelas_nama_key UNIQUE (nama)
);

-- Tabel: kelas_user (Pivot Relasi Koordinator / User dengan Kelas)
CREATE TABLE IF NOT EXISTS public.kelas_user (
    id_kelas_user uuid DEFAULT gen_random_uuid() NOT NULL,
    id_kelas uuid,
    id_user uuid,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT kelas_user_pkey PRIMARY KEY (id_kelas_user),
    CONSTRAINT kelas_user_id_kelas_id_user_key UNIQUE (id_kelas, id_user),
    CONSTRAINT kelas_user_id_kelas_fkey FOREIGN KEY (id_kelas)
        REFERENCES public.kelas(id_kelas) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT kelas_user_id_user_fkey FOREIGN KEY (id_user)
        REFERENCES public."user"(id_user) ON UPDATE CASCADE ON DELETE CASCADE
);

-- Tabel: grup_wa (Data Grup WhatsApp yang Terhubung ke Kelas)
CREATE TABLE IF NOT EXISTS public.grup_wa (
    id_grup_wa uuid DEFAULT gen_random_uuid() NOT NULL,
    id_kelas uuid,
    nama character varying(100) NOT NULL,
    id_grup character varying(50) NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT grup_wa_pkey PRIMARY KEY (id_grup_wa),
    CONSTRAINT grup_wa_id_grup_key UNIQUE (id_grup),
    CONSTRAINT grup_wa_id_kelas_fkey FOREIGN KEY (id_kelas)
        REFERENCES public.kelas(id_kelas) ON UPDATE CASCADE ON DELETE SET NULL
);

-- Tabel: ortu (Data Orang Tua / Wali Siswa)
CREATE TABLE IF NOT EXISTS public.ortu (
    id_ortu uuid DEFAULT gen_random_uuid() NOT NULL,
    nama character varying(100) NOT NULL,
    telepon character varying(30),
    email character varying(100),
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT ortu_pkey PRIMARY KEY (id_ortu)
);

-- Tabel: siswa (Data Siswa)
CREATE TABLE IF NOT EXISTS public.siswa (
    id_siswa uuid DEFAULT gen_random_uuid() NOT NULL,
    id_ortu uuid,
    id_kelas uuid,
    nama character varying(100) NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT siswa_pkey PRIMARY KEY (id_siswa),
    CONSTRAINT siswa_id_kelas_fkey FOREIGN KEY (id_kelas)
        REFERENCES public.kelas(id_kelas) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT siswa_id_ortu_fkey FOREIGN KEY (id_ortu)
        REFERENCES public.ortu(id_ortu) ON UPDATE CASCADE ON DELETE RESTRICT
);

-- Tabel: pembukuan (Catatan Pembayaran Kas / Iuran per Siswa)
CREATE TABLE IF NOT EXISTS public.pembukuan (
    id_pembukuan uuid DEFAULT gen_random_uuid() NOT NULL,
    id_siswa uuid,
    jumlah numeric(15,2) DEFAULT 0 NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT pembukuan_pkey PRIMARY KEY (id_pembukuan),
    CONSTRAINT pembukuan_id_siswa_fkey FOREIGN KEY (id_siswa)
        REFERENCES public.siswa(id_siswa) ON UPDATE CASCADE ON DELETE CASCADE
);

-- Tabel: laporan_keuangan (Arus Kas Pemasukan/Debit, Pengeluaran/Kredit, & Saldo Kelas)
CREATE TABLE IF NOT EXISTS public.laporan_keuangan (
    id_laporan_keuangan uuid DEFAULT gen_random_uuid() NOT NULL,
    id_kelas uuid,
    id_pembukuan uuid,
    tanggal date DEFAULT CURRENT_DATE NOT NULL,
    keterangan text,
    debit numeric(15,2) DEFAULT 0,
    kredit numeric(15,2) DEFAULT 0,
    saldo numeric(15,2) DEFAULT 0,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT laporan_keuangan_pkey PRIMARY KEY (id_laporan_keuangan),
    CONSTRAINT laporan_keuangan_id_kelas_fkey FOREIGN KEY (id_kelas)
        REFERENCES public.kelas(id_kelas) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT laporan_keuangan_id_pembukuan_fkey FOREIGN KEY (id_pembukuan)
        REFERENCES public.pembukuan(id_pembukuan) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_laporan_keuangan_id_pembukuan
    ON public.laporan_keuangan USING btree (id_pembukuan);

-- Tabel: agenda (Agenda Kegiatan & Pengumuman Kelas)
CREATE TABLE IF NOT EXISTS public.agenda (
    id_agenda uuid DEFAULT gen_random_uuid() NOT NULL,
    id_kelas uuid,
    tanggal date NOT NULL,
    agenda character varying(255) NOT NULL,
    mulai time without time zone,
    selesai time without time zone,
    keterangan text,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT agenda_pkey PRIMARY KEY (id_agenda),
    CONSTRAINT agenda_id_kelas_fkey FOREIGN KEY (id_kelas)
        REFERENCES public.kelas(id_kelas) ON UPDATE CASCADE ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- 4. TABEL PENDUKUNG LARAVEL & N8N CHAT MEMORY
-- -----------------------------------------------------------------------------

-- Tabel: password_reset_tokens (Fitur Lupa Password / Reset Password)
CREATE TABLE IF NOT EXISTS public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email)
);

-- Tabel: cache & cache_locks (Laravel Database Cache Store)
CREATE TABLE IF NOT EXISTS public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL,
    CONSTRAINT cache_pkey PRIMARY KEY (key)
);

CREATE TABLE IF NOT EXISTS public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL,
    CONSTRAINT cache_locks_pkey PRIMARY KEY (key)
);

-- Tabel: n8n_chat_memory (Memori Percakapan Bot WA n8n)
CREATE TABLE IF NOT EXISTS public.n8n_chat_memory (
    id SERIAL PRIMARY KEY,
    session_id character varying(255) NOT NULL,
    message jsonb NOT NULL
);

-- -----------------------------------------------------------------------------
-- 5. FUNGSI & PROSEDUR TRIGGER
-- -----------------------------------------------------------------------------

-- 5.1 Otomatis perbarui kolom updated_at saat UPDATE
CREATE OR REPLACE FUNCTION public.update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;

-- 5.2 Otomatis hash password menjadi Bcrypt ($2y$) jika di-insert/update dalam bentuk plain text
CREATE OR REPLACE FUNCTION public.encrypt_user_password_bcrypt() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
BEGIN
    IF NEW.password IS NOT NULL AND NEW.password <> '' AND left(NEW.password, 4) NOT IN ('$2y$', '$2a$', '$2b$') THEN
        NEW.password := '$2y$' || substring(crypt(NEW.password, gen_salt('bf', 12)) from 5);
    END IF;
    RETURN NEW;
END;
$_$;

-- 5.3 Hitung saldo awal saat baris laporan_keuangan di-insert/update
CREATE OR REPLACE FUNCTION public.hitung_saldo_laporan_keuangan() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    saldo_sebelumnya NUMERIC(15, 2) := 0;
BEGIN
    IF COALESCE(current_setting('korlas.skip_saldo_recalc', true), 'off') = 'on' THEN
        RETURN NEW;
    END IF;

    SELECT COALESCE(SUM(COALESCE(debit, 0) - COALESCE(kredit, 0)), 0)
    INTO saldo_sebelumnya
    FROM public.laporan_keuangan
    WHERE id_kelas = NEW.id_kelas
      AND id_laporan_keuangan IS DISTINCT FROM NEW.id_laporan_keuangan;

    NEW.saldo := saldo_sebelumnya + COALESCE(NEW.debit, 0) - COALESCE(NEW.kredit, 0);
    RETURN NEW;
END;
$$;

-- 5.4 Rekalkulasi ulang urutan saldo berjalan (running balance) untuk satu kelas
CREATE OR REPLACE FUNCTION public.rekalkulasi_saldo_kelas(p_id_kelas uuid) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF p_id_kelas IS NULL THEN
        RETURN;
    END IF;

    PERFORM set_config('korlas.skip_saldo_recalc', 'on', true);

    WITH ordered AS (
        SELECT
            id_laporan_keuangan,
            SUM(COALESCE(debit, 0) - COALESCE(kredit, 0)) OVER (
                PARTITION BY id_kelas
                ORDER BY tanggal ASC, created_at ASC, id_laporan_keuangan ASC
                ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
            ) AS new_saldo
        FROM public.laporan_keuangan
        WHERE id_kelas = p_id_kelas
    )
    UPDATE public.laporan_keuangan lk
    SET saldo = ordered.new_saldo
    FROM ordered
    WHERE lk.id_laporan_keuangan = ordered.id_laporan_keuangan
      AND lk.saldo IS DISTINCT FROM ordered.new_saldo;

    PERFORM set_config('korlas.skip_saldo_recalc', 'off', true);
END;
$$;

-- 5.5 Trigger function untuk memanggil rekalkulasi_saldo_kelas setelah perubahan di laporan_keuangan
CREATE OR REPLACE FUNCTION public.trg_fn_rekalkulasi_saldo_keuangan() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF COALESCE(current_setting('korlas.skip_saldo_recalc', true), 'off') = 'on' THEN
        RETURN NULL;
    END IF;

    IF (TG_OP = 'DELETE') THEN
        PERFORM public.rekalkulasi_saldo_kelas(OLD.id_kelas);
    ELSIF (TG_OP = 'UPDATE') THEN
        IF OLD.id_kelas IS DISTINCT FROM NEW.id_kelas THEN
            PERFORM public.rekalkulasi_saldo_kelas(OLD.id_kelas);
        END IF;
        PERFORM public.rekalkulasi_saldo_kelas(NEW.id_kelas);
    ELSIF (TG_OP = 'INSERT') THEN
        PERFORM public.rekalkulasi_saldo_kelas(NEW.id_kelas);
    END IF;

    RETURN NULL;
END;
$$;

-- 5.6 Sinkronisasi otomatis dari tabel pembukuan -> laporan_keuangan
CREATE OR REPLACE FUNCTION public.auto_debit_dari_pembukuan() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_id_kelas UUID;
    v_nama_siswa VARCHAR(100);
BEGIN
    IF COALESCE(current_setting('korlas.syncing_finances', true), 'off') = 'on' THEN
        RETURN COALESCE(NEW, OLD);
    END IF;

    PERFORM set_config('korlas.syncing_finances', 'on', true);

    IF (TG_OP = 'INSERT') THEN
        SELECT id_kelas, nama INTO v_id_kelas, v_nama_siswa
        FROM public.siswa
        WHERE id_siswa = NEW.id_siswa;

        INSERT INTO public.laporan_keuangan (id_kelas, id_pembukuan, tanggal, keterangan, debit, kredit)
        VALUES (
            v_id_kelas,
            NEW.id_pembukuan,
            CURRENT_DATE,
            'Pembayaran kas/iuran siswa: ' || COALESCE(v_nama_siswa, 'ID ' || NEW.id_siswa::TEXT),
            NEW.jumlah,
            0
        );
        PERFORM set_config('korlas.syncing_finances', 'off', true);
        RETURN NEW;

    ELSIF (TG_OP = 'UPDATE') THEN
        SELECT id_kelas, nama INTO v_id_kelas, v_nama_siswa
        FROM public.siswa
        WHERE id_siswa = NEW.id_siswa;

        UPDATE public.laporan_keuangan
        SET id_kelas = v_id_kelas,
            keterangan = 'Pembayaran kas/iuran siswa: ' || COALESCE(v_nama_siswa, 'ID ' || NEW.id_siswa::TEXT),
            debit = NEW.jumlah,
            kredit = 0
        WHERE id_pembukuan = NEW.id_pembukuan;

        IF NOT FOUND THEN
            INSERT INTO public.laporan_keuangan (id_kelas, id_pembukuan, tanggal, keterangan, debit, kredit)
            VALUES (
                v_id_kelas,
                NEW.id_pembukuan,
                CURRENT_DATE,
                'Pembayaran kas/iuran siswa: ' || COALESCE(v_nama_siswa, 'ID ' || NEW.id_siswa::TEXT),
                NEW.jumlah,
                0
            );
        END IF;

        PERFORM set_config('korlas.syncing_finances', 'off', true);
        RETURN NEW;

    ELSIF (TG_OP = 'DELETE') THEN
        DELETE FROM public.laporan_keuangan WHERE id_pembukuan = OLD.id_pembukuan;
        PERFORM set_config('korlas.syncing_finances', 'off', true);
        RETURN OLD;
    END IF;

    PERFORM set_config('korlas.syncing_finances', 'off', true);
    RETURN NULL;
END;
$$;

-- 5.7 Sinkronisasi dua arah dari laporan_keuangan -> pembukuan (saat diedit/dihapus)
CREATE OR REPLACE FUNCTION public.sync_keuangan_ke_pembukuan() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF COALESCE(current_setting('korlas.syncing_finances', true), 'off') = 'on' THEN
        RETURN COALESCE(NEW, OLD);
    END IF;

    PERFORM set_config('korlas.syncing_finances', 'on', true);

    IF (TG_OP = 'UPDATE') THEN
        IF NEW.id_pembukuan IS NOT NULL THEN
            UPDATE public.pembukuan
            SET jumlah = CASE WHEN COALESCE(NEW.debit, 0) > 0 THEN NEW.debit ELSE COALESCE(NEW.kredit, 0) END
            WHERE id_pembukuan = NEW.id_pembukuan;
        END IF;
        PERFORM set_config('korlas.syncing_finances', 'off', true);
        RETURN NEW;

    ELSIF (TG_OP = 'DELETE') THEN
        IF OLD.id_pembukuan IS NOT NULL THEN
            DELETE FROM public.pembukuan WHERE id_pembukuan = OLD.id_pembukuan;
        END IF;
        PERFORM set_config('korlas.syncing_finances', 'off', true);
        RETURN OLD;
    END IF;

    PERFORM set_config('korlas.syncing_finances', 'off', true);
    RETURN NULL;
END;
$$;

-- 5.8 Notifikasi webhook ke n8n saat agenda baru dibuat atau jadwalnya diubah (membutuhkan pg_net)
CREATE OR REPLACE FUNCTION public.notify_n8n_agenda_reminder() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF (TG_OP = 'INSERT') OR 
       (TG_OP = 'UPDATE' AND (
           OLD.tanggal IS DISTINCT FROM NEW.tanggal OR 
           OLD.mulai IS DISTINCT FROM NEW.mulai OR
           OLD.id_kelas IS DISTINCT FROM NEW.id_kelas
       )) THEN
        BEGIN
            PERFORM net.http_post(
                url := 'https://marahub.ruffe-augmented.ts.net/n8n/webhook/trigger-reminder-agenda',
                headers := '{"Content-Type": "application/json"}'::jsonb,
                body := jsonb_build_object(
                    'id_agenda', NEW.id_agenda,
                    'id_kelas', NEW.id_kelas,
                    'tanggal', NEW.tanggal,
                    'mulai', NEW.mulai
                )
            );
        EXCEPTION
            WHEN OTHERS THEN
                -- Abaikan jika ekstensi pg_net tidak aktif; aplikasi Laravel juga memiliki fallback HTTP webhook
                NULL;
        END;
    END IF;
    RETURN NEW;
END;
$$;

-- -----------------------------------------------------------------------------
-- 6. REGISTRASI TRIGGER PADA TABEL
-- -----------------------------------------------------------------------------

-- Trigger updated_at
DROP TRIGGER IF EXISTS set_timestamp_level ON public.level;
CREATE TRIGGER set_timestamp_level BEFORE UPDATE ON public.level FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

DROP TRIGGER IF EXISTS set_timestamp_role ON public.role;
CREATE TRIGGER set_timestamp_role BEFORE UPDATE ON public.role FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

DROP TRIGGER IF EXISTS set_timestamp_user ON public."user";
CREATE TRIGGER set_timestamp_user BEFORE UPDATE ON public."user" FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

DROP TRIGGER IF EXISTS set_timestamp_kelas ON public.kelas;
CREATE TRIGGER set_timestamp_kelas BEFORE UPDATE ON public.kelas FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

DROP TRIGGER IF EXISTS set_timestamp_kelas_user ON public.kelas_user;
CREATE TRIGGER set_timestamp_kelas_user BEFORE UPDATE ON public.kelas_user FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

DROP TRIGGER IF EXISTS set_timestamp_grup_wa ON public.grup_wa;
CREATE TRIGGER set_timestamp_grup_wa BEFORE UPDATE ON public.grup_wa FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

DROP TRIGGER IF EXISTS set_timestamp_ortu ON public.ortu;
CREATE TRIGGER set_timestamp_ortu BEFORE UPDATE ON public.ortu FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

DROP TRIGGER IF EXISTS set_timestamp_siswa ON public.siswa;
CREATE TRIGGER set_timestamp_siswa BEFORE UPDATE ON public.siswa FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

DROP TRIGGER IF EXISTS set_timestamp_pembukuan ON public.pembukuan;
CREATE TRIGGER set_timestamp_pembukuan BEFORE UPDATE ON public.pembukuan FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

DROP TRIGGER IF EXISTS set_timestamp_laporan_keuangan ON public.laporan_keuangan;
CREATE TRIGGER set_timestamp_laporan_keuangan BEFORE UPDATE ON public.laporan_keuangan FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

DROP TRIGGER IF EXISTS set_timestamp_agenda ON public.agenda;
CREATE TRIGGER set_timestamp_agenda BEFORE UPDATE ON public.agenda FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

-- Trigger enkripsi password user
DROP TRIGGER IF EXISTS a_trg_encrypt_user_password ON public."user";
CREATE TRIGGER a_trg_encrypt_user_password BEFORE INSERT OR UPDATE ON public."user" FOR EACH ROW EXECUTE FUNCTION public.encrypt_user_password_bcrypt();

-- Trigger sinkronisasi pembukuan & laporan_keuangan beserta kalkulasi saldo
DROP TRIGGER IF EXISTS trg_pembukuan_ke_keuangan ON public.pembukuan;
CREATE TRIGGER trg_pembukuan_ke_keuangan AFTER INSERT OR DELETE OR UPDATE ON public.pembukuan FOR EACH ROW EXECUTE FUNCTION public.auto_debit_dari_pembukuan();

DROP TRIGGER IF EXISTS trg_auto_saldo_keuangan ON public.laporan_keuangan;
CREATE TRIGGER trg_auto_saldo_keuangan BEFORE INSERT OR UPDATE ON public.laporan_keuangan FOR EACH ROW EXECUTE FUNCTION public.hitung_saldo_laporan_keuangan();

DROP TRIGGER IF EXISTS trg_after_saldo_keuangan ON public.laporan_keuangan;
CREATE TRIGGER trg_after_saldo_keuangan AFTER INSERT OR DELETE OR UPDATE OF id_kelas, tanggal, debit, kredit ON public.laporan_keuangan FOR EACH ROW EXECUTE FUNCTION public.trg_fn_rekalkulasi_saldo_keuangan();

DROP TRIGGER IF EXISTS trg_keuangan_ke_pembukuan ON public.laporan_keuangan;
CREATE TRIGGER trg_keuangan_ke_pembukuan AFTER DELETE OR UPDATE ON public.laporan_keuangan FOR EACH ROW EXECUTE FUNCTION public.sync_keuangan_ke_pembukuan();

-- Trigger reminder agenda ke n8n
DROP TRIGGER IF EXISTS trg_notify_reminder_agenda ON public.agenda;
CREATE TRIGGER trg_notify_reminder_agenda AFTER INSERT OR UPDATE ON public.agenda FOR EACH ROW EXECUTE FUNCTION public.notify_n8n_agenda_reminder();

-- -----------------------------------------------------------------------------
-- 7. SEED DATA AWAL (LEVEL & ROLE DEFAULT)
-- -----------------------------------------------------------------------------

INSERT INTO public.level (id_level, nama, keterangan)
VALUES
    ('059e6b52-6074-4705-ace8-66f131b4ce22', 'Level 1 - Full Access', 'Akses penuh ke bot, operasi CRUD lengkap (Create, Read, Update, Delete), dan fitur debugging'),
    ('b549d74c-1db2-4f4e-8573-c5516cf88bf0', 'Level 2 - Create & Read', 'Akses bot untuk melihat data (Read) dan menambah data baru (Create) tanpa hak ubah/hapus'),
    ('dcccd7f1-ec6b-402d-b03d-76044506336a', 'Level 3 - Read Only', 'Akses bot terbatas hanya untuk melihat/menanyakan informasi (Read saja)')
ON CONFLICT (nama) DO NOTHING;

INSERT INTO public.role (id_role, id_level, nama)
VALUES
    ('f62c3d1c-50bc-4b56-9ce2-178d2f9308bd', '059e6b52-6074-4705-ace8-66f131b4ce22', 'admin/operator'),
    ('649c254b-133a-45a5-92c9-4cd6b086db54', 'b549d74c-1db2-4f4e-8573-c5516cf88bf0', 'korlas/pengurus'),
    ('d417f64f-1aa2-4680-bd83-b67665c32ccb', 'dcccd7f1-ec6b-402d-b03d-76044506336a', 'user/undefined')
ON CONFLICT (nama) DO NOTHING;
