<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Rincomm') }} Verification Code</title>
</head>

<body
    style="
        margin: 0;
        padding: 0;
        background-color: #f5f7f7;
        font-family: Arial, Helvetica, sans-serif;
        color: #262626;
    ">
    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        border="0"
        style="background-color: #f5f7f7;">
        <tr>
            <td
                align="center"
                style="padding: 32px 16px;">
                <table
                    role="presentation"
                    width="100%"
                    cellspacing="0"
                    cellpadding="0"
                    border="0"
                    style="
                        max-width: 560px;
                        background-color: #ffffff;
                        border: 1px solid #e5e7eb;
                    ">
                    <tr>
                        <td
                            style="
                                padding: 24px 28px 18px;
                                border-bottom: 3px solid #008080;
                            ">
                            <div
                                style="
                                    font-size: 20px;
                                    font-weight: 700;
                                    color: #008080;
                                ">
                                {{ config('app.name', 'Rincomm') }}
                            </div>

                            <div
                                style="
                                    margin-top: 4px;
                                    font-size: 13px;
                                    color: #737373;
                                ">
                                Account Security
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 28px;">
                            <h1
                                style="
                                    margin: 0 0 14px;
                                    font-size: 22px;
                                    line-height: 1.3;
                                    color: #171717;
                                ">
                                Verification code
                            </h1>

                            <p
                                style="
                                    margin: 0 0 18px;
                                    font-size: 15px;
                                    line-height: 1.6;
                                    color: #525252;
                                ">
                                Use the code below to complete your
                                {{ $purposeLabel }}.
                            </p>

                            <table
                                role="presentation"
                                width="100%"
                                cellspacing="0"
                                cellpadding="0"
                                border="0"
                                style="margin: 20px 0;">
                                <tr>
                                    <td
                                        align="center"
                                        style="
                                            padding: 18px;
                                            background-color: #f0fdfa;
                                            border: 1px solid #99f6e4;
                                        ">
                                        <div
                                            style="
                                                font-size: 32px;
                                                font-weight: 700;
                                                letter-spacing: 8px;
                                                color: #0f766e;
                                            ">
                                            {{ $code }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <p
                                style="
                                    margin: 0 0 12px;
                                    font-size: 14px;
                                    line-height: 1.6;
                                    color: #525252;
                                ">
                                This code expires in
                                <strong>{{ $expiresInMinutes }} minutes</strong>.
                            </p>

                            <p
                                style="
                                    margin: 0;
                                    font-size: 14px;
                                    line-height: 1.6;
                                    color: #525252;
                                ">
                                Do not share this code with anyone.
                                Rincomm staff should never ask you to provide
                                your verification code.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                                padding: 18px 28px;
                                background-color: #fafafa;
                                border-top: 1px solid #e5e7eb;
                            ">
                            <p
                                style="
                                    margin: 0;
                                    font-size: 12px;
                                    line-height: 1.5;
                                    color: #737373;
                                ">
                                If you did not request this code, you can
                                ignore this email. No account action will be
                                completed without successful verification.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>