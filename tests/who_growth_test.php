<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/who_growth.php';

function expect_close(float $expected, float $actual, string $message): void
{
    if (abs($expected - $actual) > 0.00001) {
        throw new RuntimeException($message);
    }
}

$boyAtBirth = calculate_who_growth_scores('L', 0, 3.3464, 49.8842);
expect_close(0, $boyAtBirth['weight_for_age'], 'WHO male birth median should have a zero WFA score.');
expect_close(0, $boyAtBirth['height_for_age'], 'WHO male birth median should have a zero length-for-age score.');

$weightReference = load_who_lms_reference('wfa_boys.csv', 'month');
$birthLms = interpolate_who_lms($weightReference, 0);
$minusTwoValue = who_lms_value_at_z($birthLms, -2);
expect_close(-2, who_lms_z_score($minusTwoValue, $birthLms), 'The -2 SD boundary should map to z = -2.');

$belowThreeStandardDeviations = who_lms_value_at_z($birthLms, -3) - 0.1;
if (who_lms_z_score($belowThreeStandardDeviations, $birthLms) >= -3) {
    throw new RuntimeException('WHO extended z-score handling should remain below -3 SD.');
}

$outsideWeightForLengthRange = calculate_who_growth_scores('L', 0, 3.3464, 40);
if ($outsideWeightForLengthRange['weight_for_height'] !== null) {
    throw new RuntimeException('An out-of-range length should not receive a weight-for-length score.');
}

$classifications = [
    [
        'scores' => ['height_for_age' => -2.01, 'weight_for_age' => 0, 'weight_for_height' => 0],
        'expected' => 'Indikasi Stunting',
    ],
    [
        'scores' => ['height_for_age' => 0, 'weight_for_age' => -2.01, 'weight_for_height' => 0],
        'expected' => 'Kurang Gizi',
    ],
    [
        'scores' => ['height_for_age' => 0, 'weight_for_age' => 0, 'weight_for_height' => 2.01],
        'expected' => 'Perlu Penilaian',
    ],
    [
        'scores' => ['height_for_age' => 0, 'weight_for_age' => 0, 'weight_for_height' => null],
        'expected' => 'Perlu Penilaian',
    ],
    [
        'scores' => ['height_for_age' => 0, 'weight_for_age' => 0, 'weight_for_height' => 0],
        'expected' => 'Gizi Baik',
    ],
];

foreach ($classifications as $classification) {
    $actual = classify_who_growth($classification['scores']);
    if ($actual !== $classification['expected']) {
        throw new RuntimeException('WHO screening classification did not match the expected status.');
    }
}

echo "WHO growth tests passed.\n";
