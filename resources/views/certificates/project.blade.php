<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_number }}</title>
    <style>
        @page { margin: 18px; }
        body { margin: 0; color: #1e3027; font-family: DejaVu Sans, sans-serif; }
        .frame { height: 700px; border: 9px solid #315b45; padding: 10px; }
        .inner { height: 674px; border: 2px solid #c8a96b; text-align: center; position: relative; }
        .brand { margin-top: 44px; color: #315b45; font-size: 18px; font-weight: bold; letter-spacing: 3px; }
        .title { margin-top: 24px; font-family: DejaVu Serif, serif; font-size: 39px; color: #18271f; }
        .subtitle { margin-top: 8px; font-size: 13px; color: #647168; }
        .project { margin: 24px auto 0; width: 78%; border-bottom: 1px solid #b8c1ba; padding-bottom: 11px; font-family: DejaVu Serif, serif; font-size: 26px; }
        .owner { margin-top: 10px; font-size: 13px; }
        .level { margin: 26px auto 0; width: 250px; padding: 13px; background: #315b45; color: white; font-size: 25px; font-weight: bold; }
        .score { margin-top: 11px; font-size: 14px; }
        .details { margin: 25px auto 0; width: 76%; font-size: 11px; color: #526158; }
        .details td { width: 33%; padding: 5px; }
        .number { font-weight: bold; color: #263a2f; }
        .qr { position: absolute; right: 42px; bottom: 34px; width: 95px; height: 95px; }
        .verify { position: absolute; right: 25px; bottom: 12px; width: 130px; font-size: 7px; color: #66736b; }
        .issued { position: absolute; left: 42px; bottom: 45px; width: 220px; text-align: left; font-size: 10px; }
        .disclaimer { position: absolute; bottom: 13px; left: 175px; right: 175px; font-size: 7px; color: #778079; }
    </style>
</head>
<body>
<div class="frame"><div class="inner">
    <div class="brand">PROFORMAX</div>
    <div class="title">Green Building Assessment Certificate</div>
    <div class="subtitle">This document records the final reviewed Actual assessment completed in ProFormaX.</div>
    <div class="project">{{ $snapshot['project_name'] }}</div>
    <div class="owner">Awarded to <strong>{{ $snapshot['owner_name'] ?: 'Project Owner' }}</strong></div>
    <div class="level">{{ $certificate->certification_level }}</div>
    <div class="score">Approved Actual score: <strong>{{ $certificate->approved_actual_score }} / {{ $certificate->maximum_score }}</strong></div>
    <table class="details"><tr>
        <td>Building type<br><strong>{{ $snapshot['building_type'] ?: 'Not specified' }}</strong></td>
        <td>Location<br><strong>{{ $snapshot['location'] ?: 'Not specified' }}</strong></td>
        <td>Certificate number<br><span class="number">{{ $certificate->certificate_number }}</span></td>
    </tr></table>
    <div class="issued">Issued on <strong>{{ $certificate->issued_at->format('d F Y') }}</strong><br>Authorized by {{ trim(($certificate->issuer?->first_name ?? '').' '.($certificate->issuer?->last_name ?? '')) ?: 'ProFormaX Administrator' }}</div>
    <img class="qr" src="{{ $qrDataUri }}" alt="Verification QR code">
    <div class="verify">Scan to verify<br>{{ $certificate->verification_code }}</div>
    <div class="disclaimer">This is a ProFormaX assessment record. It is not an official Green Building Index certificate unless issued under authorization from the relevant certification body.</div>
</div></div>
</body>
</html>
