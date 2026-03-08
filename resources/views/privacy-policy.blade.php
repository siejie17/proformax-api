<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy - ProFormaX</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 60px;
        }

        h1 {
            font-size: 2.5rem;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .last-updated {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 40px;
        }

        h2 {
            font-size: 1.5rem;
            color: #1e293b;
            margin-top: 35px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }

        h3 {
            font-size: 1.1rem;
            color: #334155;
            margin-top: 20px;
            margin-bottom: 12px;
            font-weight: 600;
        }

        p {
            color: #475569;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        ul {
            list-style: none;
            padding-left: 0;
            margin-bottom: 20px;
        }

        li {
            color: #475569;
            line-height: 1.8;
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
        }

        li:before {
            content: "•";
            color: #2563eb;
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        strong {
            color: #1e293b;
            font-weight: 600;
        }

        a {
            color: #2563eb;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        .important {
            color: #1e293b;
            font-weight: 600;
            margin-top: 15px;
        }

        .italic {
            font-style: italic;
            color: #64748b;
        }

        section {
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px;
            }

            h1 {
                font-size: 2rem;
            }

            h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>PRIVACY POLICY FOR PROFORMAX</h1>
        <p class="last-updated">Last Updated: March 1, 2026</p>

        <section>
            <h2>1. INTRODUCTION</h2>
            <p>ProFormaX ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our mobile application.</p>
        </section>

        <section>
            <h2>2. INFORMATION WE COLLECT</h2>
            <p>We collect information in the following ways:</p>
            
            <h3>2.1 Information You Provide Directly:</h3>
            <ul>
                <li><strong>Account Information:</strong> Name, email address, password, phone number, and profile picture</li>
                <li><strong>Project Information:</strong> Cost data, project details, measurements, and file uploads</li>
                <li><strong>Team Information:</strong> Team member details when collaborating</li>
            </ul>

            <h3>2.2 Automatically Collected Information:</h3>
            <ul>
                <li><strong>Device Information:</strong> Device type, operating system, unique device identifiers</li>
                <li><strong>Usage Data:</strong> Features accessed, actions taken, time spent in app, interaction patterns</li>
                <li><strong>Crash Reports and Performance Data:</strong> App performance and error logs</li>
            </ul>

            <h3>2.3 Third-Party Information:</h3>
            <ul>
                <li>Analytics data through Google Analytics</li>
                <li>AI Processing data sent to Google Gemini API</li>
            </ul>
        </section>

        <section>
            <h2>3. HOW WE USE YOUR INFORMATION</h2>
            <p>We use collected information to:</p>
            <ul>
                <li>Create and maintain your account</li>
                <li>Provide app functionality (cost calculations, 3D modeling, project management)</li>
                <li>Improve and personalize your experience</li>
                <li>Communicate important updates and support messages</li>
                <li>Process AI-assisted features and recommendations</li>
                <li>Analyze usage patterns to improve our services</li>
                <li>Comply with legal obligations</li>
                <li>Prevent fraud and secure your account</li>
            </ul>
        </section>

        <section>
            <h2>4. THIRD-PARTY SHARING</h2>
            <p>We may share your information with:</p>
            <ul>
                <li>Google/Firebase (for authentication, analytics, and cloud services)</li>
                <li>Google Gemini AI (for AI assistant features)</li>
                <li>Service providers who help operate the app</li>
                <li>Legal authorities when required by law</li>
            </ul>
            <p class="important">We do NOT sell your personal data to third parties.</p>
        </section>

        <section>
            <h2>5. DATA SECURITY</h2>
            <p>We implement appropriate technical and organizational security measures including:</p>
            <ul>
                <li>Encryption of data in transit (SSL/TLS)</li>
                <li>Secure authentication protocols</li>
                <li>Regular security assessments</li>
                <li>Limited employee access to personal data</li>
            </ul>
            <p class="italic">However, no method of transmission over the internet is 100% secure.</p>
        </section>

        <section>
            <h2>6. DATA RETENTION</h2>
            <ul>
                <li>Account data is retained as long as you maintain an active account</li>
                <li>Usage analytics are retained for up to 12 months</li>
                <li>You can request deletion of your data at any time</li>
            </ul>
        </section>

        <section>
            <h2>7. YOUR PRIVACY RIGHTS</h2>
            <p>You have the right to:</p>
            <ul>
                <li>Access your personal data</li>
                <li>Correct inaccurate information</li>
                <li>Request deletion of your data</li>
                <li>Opt-out of certain data processing</li>
                <li>Export your data</li>
            </ul>
            <p>To exercise these rights, contact us at <a href="mailto:proformax01@gmail.com">proformax01@gmail.com</a></p>
        </section>

        <section>
            <h2>8. CHILDREN'S PRIVACY</h2>
            <p>ProFormaX is not intended for children under 13. We do not knowingly collect information from children under 13. If we discover we have collected such information, we will delete it immediately.</p>
        </section>

        <section>
            <h2>9. CONTACT US</h2>
            <p>If you have questions about this Privacy Policy, please contact:</p>
            <p><strong>ProFormaX Support</strong></p>
            <p>Email: <a href="mailto:proformax01@gmail.com">proformax01@gmail.com</a></p>
        </section>

        <section>
            <h2>10. POLICY CHANGES</h2>
            <p>We may update this Privacy Policy periodically. Significant changes will be notified through the app or email. Continued use of the app constitutes acceptance of the updated policy.</p>
        </section>
    </div>
</body>

</html>
