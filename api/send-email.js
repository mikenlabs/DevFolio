const { URLSearchParams } = require('url');

module.exports = async function (req, res) {
    if (req.method !== 'POST') {
        return res.status(405).json({ success: false, message: 'Method not allowed' });
    }

    let body = req.body;

    if (typeof body === 'string') {
        try {
            body = JSON.parse(body);
        } catch {
            try {
                const params = new URLSearchParams(body);
                body = Object.fromEntries(params.entries());
            } catch {
                return res.status(400).json({ success: false, message: 'Invalid request body' });
            }
        }
    }

    const name = (body.name || '').trim();
    const email = (body.email || '').trim();
    const subject = (body.subject || '').trim();
    const message = (body.message || '').trim();

    const jsonResponse = (status, data) => {
        res.status(status).json(data);
    };

    if (!name || !email || !message) {
        return jsonResponse(200, { success: false, message: 'Please fill in all required fields.' });
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        return jsonResponse(200, { success: false, message: 'Please provide a valid email address.' });
    }

    const apiKey = process.env.BREVO_API_KEY;
    const fromEmail = process.env.BREVO_FROM_EMAIL || 'noreply@example.com';
    const toEmail = process.env.BREVO_TO_EMAIL || 'you@example.com';

    if (!apiKey) {
        return jsonResponse(200, { success: false, message: 'Server configuration error. Please try again later.' });
    }

    const sanitize = (str) => str.replace(/[<>]/g, (c) => (c === '<' ? '&lt;' : '&gt;'));

    const htmlContent = `
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <style>
        body { font-family: 'Montserrat', sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 30px; }
        .header { background: #096727; color: #fff; padding: 25px; text-align: center; }
        .header h2 { margin: 0; font-size: 22px; }
        .content { padding: 25px; background: #f9f9f9; }
        .field { margin-bottom: 15px; }
        .label { font-weight: 600; color: #096727; display: block; margin-bottom: 5px; font-size: 14px; }
        .value { font-size: 16px; }
        .message-box { background: #fff; padding: 15px; border-left: 4px solid #11ce4d; border-radius: 4px; margin-top: 10px; white-space: pre-wrap; }
        .footer { text-align: center; padding: 15px; color: #797979; font-size: 13px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>New Message from Portfolio</h2>
        </div>
        <div class='content'>
            <div class='field'>
                <span class='label'>Name</span>
                <span class='value'>${sanitize(name)}</span>
            </div>
            <div class='field'>
                <span class='label'>Email</span>
                <span class='value'>${sanitize(email)}</span>
            </div>
            <div class='field'>
                <span class='label'>Subject</span>
                <span class='value'>${sanitize(subject || 'Portfolio Contact')}</span>
            </div>
            <div class='field'>
                <span class='label'>Message</span>
                <div class='message-box'>${sanitize(message)}</div>
            </div>
        </div>
        <div class='footer'>
            Sent from Uche Michael's portfolio contact form
        </div>
    </div>
</body>
</html>`;

    const payload = JSON.stringify({
        sender: { email: fromEmail, name: 'Portfolio Contact Form' },
        to: [{ email: toEmail, name: name }],
        replyTo: { email, name },
        subject: '[Portfolio] ' + (subject || 'New message'),
        htmlContent,
    });

    try {
        const response = await fetch('https://api.brevo.com/v3/smtpEmail', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'api-key': apiKey,
            },
            body: payload,
        });

        const result = await response.json();

        if (response.ok) {
            jsonResponse(200, { success: true, message: 'Message sent successfully! I will get back to you soon.' });
        } else {
            jsonResponse(200, { success: false, message: 'Error: ' + (result.message || 'Failed to send message. Please try again later.') });
        }
    } catch (err) {
        jsonResponse(200, { success: false, message: 'Connection error. Please try again later.' });
    }
};
