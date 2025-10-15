<?php
require_once 'includes/db.php';
// session_start() might be in header.php, but if not, add it here for consistency
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/header.php';
?>

<div class="container mt-5 mb-5">
    <h1 class="text-center mb-4">bundled packages terms - claims procedure</h1>

    <div class="card p-4 glassmorphism-card">
        <p class="lead">
            These terms and conditions ("Terms") govern your use of our Online Payment Aggregation Services
            on a Service Fee Model. By availing these services, you agree to be bound by these Terms.
            Please read them carefully.
        </p>

        <h2 class="mt-4">1. Definitions</h2>
        <ul>
            <li><strong>Service Fee Model:</strong> Refers to the arrangement where fees are charged based on a percentage of transaction value or a fixed fee per transaction.</li>
            <li><strong>Merchant:</strong> The entity or individual utilizing the Online Payment Aggregation Services.</li>
            <li><strong>Payment Aggregation Services:</strong> Services provided to facilitate online payment acceptance and processing.</li>
            </ul>

        <h2 class="mt-4">2. Services Offered</h2>
        <p>
            We provide payment gateway services that enable merchants to accept various online payment methods
            from their customers, including credit/debit cards, net banking, UPI, and digital wallets.
            Our services include transaction processing, settlement, and reporting.
        </p>

        <h2 class="mt-4">3. Fees and Charges</h2>
        <ul>
            <li>A service fee, as mutually agreed upon and specified in your Merchant Agreement, will be
                deducted from each successful transaction.</li>
            <li>Additional charges may apply for specific services or chargebacks, as detailed in your agreement.</li>
            <li>All fees are subject to applicable taxes.</li>
        </ul>

        <h2 class="mt-4">4. Merchant Obligations</h2>
        <ul>
            <li>The Merchant must ensure compliance with all applicable laws and regulations, including
                those related to consumer protection, data privacy, and anti-money laundering (AML).</li>
            <li>The Merchant must provide accurate and complete information during the onboarding process
                and keep it updated.</li>
            <li>The Merchant is responsible for the delivery of goods or services to customers.</li>
        </ul>

        <h2 class="mt-4">5. Our Responsibilities</h2>
        <ul>
            <li>We will provide the Payment Aggregation Services with reasonable care and skill.</li>
            <li>We will ensure the security of transaction data in our control in accordance with industry standards.</li>
            <li>We will facilitate settlement of funds to the Merchant's designated bank account, subject to agreed timelines.</li>
        </ul>

        <h2 class="mt-4">6. Indemnification</h2>
        <p>
            The Merchant agrees to indemnify and hold harmless [Your Company Name] and its affiliates,
            officers, directors, employees, and agents from any and all claims, liabilities, damages,
            losses, and expenses, including reasonable attorneys' fees and costs, arising out of or in
            any way connected with the Merchant's use of the Payment Aggregation Services,
            breach of these Terms, or violation of any applicable laws.
        </p>

        <h2 class="mt-4">7. Termination</h2>
        <p>
            Either party may terminate these Terms and the associated services by providing written notice
            as per the Merchant Agreement. Termination will not affect any rights or obligations that
            accrued prior to termination.
        </p>

        <h2 class="mt-4">8. Governing Law</h2>
        <p>
            These Terms shall be governed by and construed in accordance with the laws of [Your Country/State].
            Any disputes arising under or in connection with these Terms shall be subject to the exclusive
            jurisdiction of the courts located in [Your City, Your Country/State].
        </p>

        <h2 class="mt-4">9. Amendments</h2>
        <p>
            We reserve the right to amend these Terms at any time. Any changes will be effective immediately
            upon posting the revised Terms on our website. Your continued use of the services after
            such amendments constitutes your acceptance of the revised Terms.
        </p>

        <p class="mt-5"><strong>Effective Date: [Date]</strong></p>
        <p>For any questions regarding these terms, please contact our support team.</p>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>