<?php

namespace App\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;
use DateTimeInterface;

class ElectionModel extends Model
{
    public const STATUS_UPCOMING = 'UPCOMING';
    public const STATUS_ONGOING  = 'ONGOING';
    public const STATUS_FINISHED = 'FINISHED';

    protected $table         = 'elections';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'nama',
        'tahun',
        'start_at',
        'end_at',
        'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nama'     => 'required|max_length[150]',
        'tahun'    => 'required|is_natural_no_zero',
        'start_at' => 'required|valid_date[Y-m-d H:i:s]',
        'end_at'   => 'required|valid_date[Y-m-d H:i:s]',
        'status'   => 'permit_empty|in_list[UPCOMING,ONGOING,FINISHED]',
    ];

    /**
     * Election yang sedang berlaku (satu sistem = satu election aktif, yang terbaru).
     * Kolom status dikembalikan sudah sesuai jadwal & waktu server, dan
     * disinkronkan ke database bila berbeda.
     */
    public function getCurrentElection(): ?array
    {
        $election = $this->orderBy('id', 'DESC')->first();

        return $election === null ? null : $this->syncStatus($election);
    }

    /**
     * Status election dihitung dari jadwal terhadap waktu SERVER
     * (MASTER section 16). Kolom status hanya cermin, bukan sumber kebenaran.
     *
     * - now <  start_at          : UPCOMING
     * - start_at <= now < end_at : ONGOING
     * - now >= end_at            : FINISHED
     */
    public function resolveStatus(array $election, ?DateTimeInterface $now = null): string
    {
        $now ??= Time::now();
        $nowTs = $now->getTimestamp();

        if ($nowTs < Time::parse($election['start_at'])->getTimestamp()) {
            return self::STATUS_UPCOMING;
        }

        if ($nowTs >= Time::parse($election['end_at'])->getTimestamp()) {
            return self::STATUS_FINISHED;
        }

        return self::STATUS_ONGOING;
    }

    /**
     * Samakan kolom status dengan hasil resolveStatus() dan kembalikan
     * election dengan status efektif.
     */
    public function syncStatus(array $election, ?DateTimeInterface $now = null): array
    {
        $status = $this->resolveStatus($election, $now);

        if (($election['status'] ?? null) !== $status) {
            $this->builder()
                ->where('id', $election['id'])
                ->update(['status' => $status, 'updated_at' => Time::now()->toDateTimeString()]);
            $election['status'] = $status;
        }

        return $election;
    }
}
