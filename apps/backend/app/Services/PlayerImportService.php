<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Player;
use Carbon\Carbon;

class PlayerImportService
{
    public const COLUMN_LAST_NAME = 'Nom';

    public const COLUMN_FIRST_NAME = 'Prénom';

    public const COLUMN_LICENSE = 'Licence';

    public const COLUMN_BIRTH_DATE = 'Date naissance';

    public const COLUMN_EMAIL = 'Email';

    public const COLUMN_PHONE = 'Téléphone';

    public const COLUMN_CATEGORY = 'Catégorie';

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function importFromPath(string $filePath): array
    {
        $content = $this->readAndDecode($filePath);
        $rows = $this->parseCsv($content);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2; // +2 : ligne 1 = entête, index 0-based

            $licenseNumber = trim($row[self::COLUMN_LICENSE] ?? '');

            if ($licenseNumber === '') {
                $errors[] = "Ligne {$line} : numéro de licence manquant, ignorée.";
                $skipped++;
                continue;
            }

            try {
                $data = $this->mapRow($row);
                $existing = Player::where('ffbad_license_number', $licenseNumber)->first();

                if ($existing instanceof Player) {
                    $existing->update($data);
                    $updated++;
                } else {
                    Player::create(array_merge($data, ['ffbad_license_number' => $licenseNumber]));
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Ligne {$line} (licence {$licenseNumber}) : {$e->getMessage()}";
                $skipped++;
            }
        }

        return compact('created', 'updated', 'skipped', 'errors');
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, string|null>
     */
    private function mapRow(array $row): array
    {
        $birthDate = null;
        $rawDate = trim($row[self::COLUMN_BIRTH_DATE] ?? '');

        if ($rawDate !== '') {
            try {
                $birthDate = Carbon::createFromFormat('d/m/Y', $rawDate)?->format('Y-m-d');
            } catch (\Throwable) {
                $birthDate = null;
            }
        }

        $phone = trim($row[self::COLUMN_PHONE] ?? '');
        $email = trim($row[self::COLUMN_EMAIL] ?? '');

        return [
            'last_name'      => trim($row[self::COLUMN_LAST_NAME] ?? ''),
            'first_name'     => trim($row[self::COLUMN_FIRST_NAME] ?? ''),
            'email'          => $email !== '' ? $email : null,
            'phone'          => $phone !== '' ? $phone : null,
            'birth_date'     => $birthDate,
            'ffbad_category' => trim($row[self::COLUMN_CATEGORY] ?? '') ?: null,
        ];
    }

    /**
     * Lit le fichier et convertit en UTF-8 si nécessaire.
     *
     * Les exports FFBad/Poona ont un encodage mixte :
     * - La ligne d'en-tête utilise ISO-8859-1 (é = 0xe9)
     * - Les lignes de données utilisent MacRoman (é = 0x8e)
     */
    private function readAndDecode(string $path): string
    {
        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new \RuntimeException('Impossible de lire le fichier.');
        }

        if (preg_match('//u', $raw) === 1) {
            return $raw;
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw);

        if ($lines === false || count($lines) === 0) {
            return $raw;
        }

        // L'en-tête est en ISO-8859-1
        $header = mb_convert_encoding(array_shift($lines), 'UTF-8', 'ISO-8859-1');

        // Les données sont en MacRoman
        $dataLines = [];
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $converted = iconv('MACINTOSH', 'UTF-8', $line);
            $dataLines[] = $converted !== false ? $converted : $line;
        }

        return implode("\n", [$header, ...$dataLines]);
    }

    /**
     * Parse le CSV (séparateur `;`) et retourne un tableau associatif par ligne.
     *
     * @return list<array<string, string>>
     */
    private function parseCsv(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content));

        if ($lines === false || count($lines) < 2) {
            return [];
        }

        /** @var list<string> $headers */
        $headers = array_map(
            fn (string|null $h): string => trim((string) $h),
            str_getcsv(array_shift($lines), separator: ';'),
        );

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cols = str_getcsv($line, separator: ';');
            $row = [];

            foreach ($headers as $i => $header) {
                $row[$header] = $cols[$i] ?? '';
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
