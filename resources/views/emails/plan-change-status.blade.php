<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rincomm plan change</title>
</head>
<body style="font-family:Arial,sans-serif;line-height:1.6;color:#172b36">
    <h2>Rincomm plan change {{ $status }}</h2>

    <p>Hello {{ $customerName }},</p>

    <p>Your plan-change request from <strong>{{ $oldPlanName }}</strong>
        to <strong>{{ $newPlanName }}</strong> has been <strong>{{ $status }}</strong>.</p>

    @if ($status === 'approved')
        <p>Your new internet plan and renewed contract took effect on
            <strong>{{ $effectiveBillingDate }}</strong> when your request was approved.
            Your new monthly plan rate is now effective for future billing cycles
            that have not yet been invoiced. Already-issued invoices remain unchanged.</p>
    @endif

    @if ($reviewNotes)
        <p>Administrator notes: {{ $reviewNotes }}</p>
    @endif

    <p>For questions, please contact Rincomm support.</p>
</body>
</html>
