<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rincomm Support Ticket</title>
</head>
<body style="margin:0;padding:24px;background:#f5f7f7;font-family:Arial,sans-serif;color:#262626;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">

        <div style="padding:20px 24px;border-bottom:3px solid #008080;">
            <h1 style="margin:0;font-size:20px;color:#008080;">
                Rincomm Support
            </h1>
        </div>

        <div style="padding:24px;">
            <p style="margin-top:0;">
                Hello {{ $customerName }},
            </p>

            @if ($created)
                <p>
                    Your support ticket has been received successfully.
                </p>
            @else
                <p>
                    The status of your support ticket has been updated.
                </p>
            @endif

            <div style="margin:20px 0;padding:16px;background:#f5f7f7;border-radius:8px;">
                <p style="margin:0 0 10px;">
                    <strong>Ticket:</strong> {{ $ticketNumber }}
                </p>

                <p style="margin:0 0 10px;">
                    <strong>Subject:</strong> {{ $ticketSubject }}
                </p>

                <p style="margin:0;">
                    <strong>Status:</strong>
                    {{ ucwords(str_replace('_', ' ', $status)) }}
                </p>
            </div>

            <p>
                Sign in to your Rincomm subscriber account to review
                your ticket and any responses from the support team.
            </p>

            <p style="margin-bottom:0;">
                Thank you,<br>
                <strong>Rincomm Support Team</strong>
            </p>
        </div>

        <div style="padding:14px 24px;border-top:1px solid #e5e7eb;color:#737373;font-size:12px;">
            This is an automated support ticket notification.
        </div>
    </div>
</body>
</html>