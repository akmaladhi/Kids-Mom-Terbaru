<?php

declare(strict_types=1);

function load_who_lms_reference(string $filename, string $axis): array
{
    $allowedFiles = [
        'lhfa_boys.csv',
        'lhfa_girls.csv',
        'wfa_boys.csv',
        'wfa_girls.csv',
        'wfl_boys.csv',
        'wfl_girls.csv',
        'wfh_boys.csv',
        'wfh_girls.csv',
    ];

    if (!in_array($filename, $allowedFiles, true)) {
        throw new InvalidArgumentException('Referensi pertumbuhan tidak valid.');
    }

    $expectedHeader = [$axis, 'L', 'M', 'S'];
    $path = __DIR__ . '/../data/who/' . $filename;
    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException('Berkas referensi WHO tidak dapat dibuka.');
    }

    try {
        $header = fgetcsv($handle, 0, ',', '"', '');
        if ($header !== $expectedHeader) {
            throw new RuntimeException('Format berkas referensi WHO tidak sesuai.');
        }

        $rows = [];
        while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if (
                count($values) !== 4
                || !is_numeric($values[0])
                || !is_numeric($values[1])
                || !is_numeric($values[2])
                || !is_numeric($values[3])
            ) {
                throw new RuntimeException('Data pada berkas referensi WHO tidak valid.');
            }

            $row = [
                'axis' => (float) $values[0],
                'l' => (float) $values[1],
                'm' => (float) $values[2],
                's' => (float) $values[3],
            ];
            if ($row['m'] <= 0 || $row['s'] <= 0) {
                throw new RuntimeException('Parameter LMS pada referensi WHO tidak valid.');
            }
            $rows[] = $row;
        }
    } finally {
        fclose($handle);
    }

    if (count($rows) < 2) {
        throw new RuntimeException('Data referensi WHO tidak lengkap.');
    }

    return $rows;
}

function interpolate_who_lms(array $rows, float $axisValue): array
{
    $lastIndex = count($rows) - 1;
    if ($axisValue < $rows[0]['axis'] || $axisValue > $rows[$lastIndex]['axis']) {
        throw new OutOfRangeException('Nilai pengukuran berada di luar rentang tabel WHO.');
    }

    for ($index = 1; $index <= $lastIndex; $index++) {
        $right = $rows[$index];
        if ($axisValue > $right['axis']) {
            continue;
        }

        $left = $rows[$index - 1];
        if ($axisValue === $right['axis']) {
            return ['l' => $right['l'], 'm' => $right['m'], 's' => $right['s']];
        }

        $fraction = ($axisValue - $left['axis']) / ($right['axis'] - $left['axis']);
        return [
            'l' => $left['l'] + (($right['l'] - $left['l']) * $fraction),
            'm' => $left['m'] + (($right['m'] - $left['m']) * $fraction),
            's' => $left['s'] + (($right['s'] - $left['s']) * $fraction),
        ];
    }

    return [
        'l' => $rows[$lastIndex]['l'],
        'm' => $rows[$lastIndex]['m'],
        's' => $rows[$lastIndex]['s'],
    ];
}

function who_lms_z_score(float $measurement, array $lms): float
{
    if ($measurement <= 0) {
        throw new InvalidArgumentException('Nilai pengukuran harus lebih dari nol.');
    }

    if (abs($lms['l']) < 0.000000001) {
        $zScore = log($measurement / $lms['m']) / $lms['s'];
    } else {
        $zScore = (pow($measurement / $lms['m'], $lms['l']) - 1)
            / ($lms['l'] * $lms['s']);
    }

    if (!is_finite($zScore)) {
        throw new RuntimeException('Skor pertumbuhan tidak dapat dihitung.');
    }

    if ($zScore < -3 || $zScore > 3) {
        $sd2 = who_lms_value_at_z($lms, $zScore < -3 ? -2 : 2);
        $sd3 = who_lms_value_at_z($lms, $zScore < -3 ? -3 : 3);

        if ($zScore < -3 && $sd2 > $sd3) {
            $zScore = -3 - (($sd3 - $measurement) / ($sd2 - $sd3));
        } elseif ($zScore > 3 && $sd3 > $sd2) {
            $zScore = 3 + (($measurement - $sd3) / ($sd3 - $sd2));
        }
    }

    return $zScore;
}

function who_lms_value_at_z(array $lms, float $zScore): float
{
    if (abs($lms['l']) < 0.000000001) {
        return $lms['m'] * exp($lms['s'] * $zScore);
    }

    $base = 1 + ($lms['l'] * $lms['s'] * $zScore);
    if ($base <= 0) {
        throw new RuntimeException('Batas skor WHO tidak dapat dihitung.');
    }

    return $lms['m'] * pow($base, 1 / $lms['l']);
}

function calculate_who_growth_scores(
    string $sex,
    float $ageMonths,
    float $weightKg,
    float $heightCm
): array {
    if (!in_array($sex, ['L', 'P'], true) || $ageMonths < 0 || $ageMonths > 60) {
        throw new InvalidArgumentException('Usia atau jenis kelamin di luar cakupan tabel WHO.');
    }

    $referenceSex = $sex === 'L' ? 'boys' : 'girls';
    $heightIndicator = $ageMonths < 24 ? 'wfl' : 'wfh';
    $heightAxis = $ageMonths < 24 ? 'length_cm' : 'height_cm';

    $heightForAgeRows = load_who_lms_reference('lhfa_' . $referenceSex . '.csv', 'month');
    $weightForAgeRows = load_who_lms_reference('wfa_' . $referenceSex . '.csv', 'month');
    $weightForHeightRows = load_who_lms_reference(
        $heightIndicator . '_' . $referenceSex . '.csv',
        $heightAxis
    );

    $heightForAge = who_lms_z_score(
        $heightCm,
        interpolate_who_lms($heightForAgeRows, $ageMonths)
    );
    $weightForAge = who_lms_z_score(
        $weightKg,
        interpolate_who_lms($weightForAgeRows, $ageMonths)
    );

    $weightForHeight = null;
    $firstHeight = $weightForHeightRows[0]['axis'];
    $lastHeight = $weightForHeightRows[count($weightForHeightRows) - 1]['axis'];
    if ($heightCm >= $firstHeight && $heightCm <= $lastHeight) {
        $weightForHeight = who_lms_z_score(
            $weightKg,
            interpolate_who_lms($weightForHeightRows, $heightCm)
        );
    }

    return [
        'height_for_age' => $heightForAge,
        'weight_for_age' => $weightForAge,
        'weight_for_height' => $weightForHeight,
    ];
}

function classify_who_growth(array $scores): string
{
    $heightForAge = $scores['height_for_age'];
    $weightForAge = $scores['weight_for_age'];
    $weightForHeight = $scores['weight_for_height'];

    if ($heightForAge < -2) {
        return 'Indikasi Stunting';
    }

    if ($weightForAge < -2 || ($weightForHeight !== null && $weightForHeight < -2)) {
        return 'Kurang Gizi';
    }

    if ($weightForHeight === null || $weightForHeight > 2) {
        return 'Perlu Penilaian';
    }

    return 'Gizi Baik';
}
