@extends('frontend.layout.main')
@section('body')
    <!-- Policy Header -->
    <div class="container-fluid bg-light-gradient py-24 mt-5">
        <div class="container py-24">
            <div class="col-12">
                <div class="text-wrapper">
                    <h2 class="fw-900">
                        {{ env('APP_NAME', 'Engagyo') }} | Terms of Services
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Area Privacy Policy -->
    <div class="container-fluid bg-light-white py-5">
        <div class="container">
            <div class="text-wrapper">
                <h2>Terms of Service</h2>
                <p>
                    By Using {{ env('APP_NAME', 'Engagyo') }} (https://{{ strtolower(env('APP_NAME', 'Engagyo')) }}.com),
                    you
                    accept the
                    following terms and conditions.
                </p>
                <p>
                    If you accept or agree to these terms on behalf of a company or other legal entity, you represent and
                    warrant that you have the authority to bind that company or other legal entity to these terms and, in
                    such an event, “YOU” and “YOUR” will refer and apply to that company or other legal entity.
                </p>
                <h2>
                    What is {{ env('APP_NAME', 'Engagyo') }}
                </h2>
                <p>
                    It is a network which empowers it’s affiliate to get most out of their web properties(Facebook, twitter
                    etc.) using it’s state of art traffic monitoring system and competitive CPM rates.
                </p>
                <h2>
                    Affiliate Requirement
                </h2>
                <p>
                    You agree that any and all information you have provided during {{ env('APP_NAME', 'Engagyo') }}
                    Registration Process is true,
                    accurate and complete. You also agree to update your personal information if required.
                    {{ env('APP_NAME', 'Engagyo') }}
                    reserves the right to suspend or terminate your account, in whole or in part, or prohibit your further
                    use of the service, at any time, if any suspicious activity is noticed in your account.
                </p>
                <h2>
                    Account Password and Security
                </h2>
                <p>
                    Upon completing the registration process you will receive a password and account ID. You and you alone
                    are solely responsible for maintaining the confidentiality of your password and information associated
                    with your account that you desire to remain confidential. We do not save your password, it is encrypted
                    as soon as it is created. So your account is safe and can not be hacked. You also agree that you are
                    responsible for any and all activities that may take place, or occur under your password and account.
                    You further agree to notify {{ env('APP_NAME', 'Engagyo') }} in the event your password or account has
                    been used without the
                    proper authorization or there are other breaches of security of which you become aware.
                    {{ env('APP_NAME', 'Engagyo') }} will
                    not be responsible or liable for any loss or damage incurred. {{ env('APP_NAME', 'Engagyo') }} prohibits
                    the sale or transfer
                    of control of any {{ env('APP_NAME', 'Engagyo') }} account by the registered account holder to any other
                    individual or party.
                </p>
                <h2>
                    {{ env('APP_NAME', 'Engagyo') }} Program Rules
                </h2>
                <ul>
                    <li>
                        Your account will be banned as soon as you start spamming a link.
                    </li>
                    <li>
                        Your account will be banned if any invalid activity is found in your account.
                    </li>
                    <li>
                        You can not mislead the audience, change the thumbnail or any text part.
                    </li>
                    <li>
                        Your account will be blocked if we detect any bot or fake traffic from your account. Also if you
                        generate clicks or impressions through any automated, deceptive, fraudulent or other malicious
                        means, your account will be banned.
                    </li>
                    <li>
                        We count only one click per link from each IP in a day. Repeated clicks from same IP will not be
                        counted.
                    </li>
                    <li>
                        We will ban your account if you engage in any action or practice that devalues
                        {{ env('APP_NAME', 'Engagyo') }}s
                        reputation or goodwill.
                    </li>
                    <li>
                        High bounce rate (above 65%) is a direct indication of suspicious activity and so is not allowed on
                        {{ env('APP_NAME', 'Engagyo') }}. Your account might be banned due an unusually high bounce rate
                        also.
                    </li>
                    <li>
                        All the payments will be done only on Monday and Tuesday depending upon the amount due in your
                        account.
                    </li>
                    <li>
                        If we temporarily block any user, they can appeal us within a week for unblocking. If you don't
                        contact within a week, we will ban your account permanently, you will be banned and no further
                        questions will be entertained.
                    </li>
                    <li>
                        If anyone of you are buying domains with the name WittyFeed or {{ env('APP_NAME', 'Engagyo') }} or
                        by any other adult name
                        or any bad language we won't activate it, thus the amount you spend to buy that domain will be
                        wasted.
                    </li>
                    <li>
                        <b>
                            In case of violating any rules as described in the agreement, {{ env('APP_NAME', 'Engagyo') }}
                            reserves the right to
                            suspend or terminate your account, in whole or in part, or prohibit your further use of the
                            service, at any time, without giving any prior notice, and all the money in your account will be
                            seized.
                        </b>
                    </li>
                </ul>
                <h2>
                    Earnings and Payments
                </h2>
                <p>
                    You agree to provide {{ env('APP_NAME', 'Engagyo') }} with information to enable remittance of your
                    earning on the site.
                    {{ env('APP_NAME', 'Engagyo') }} reserves the right to process payment for services rendered. By using
                    the service, the user
                    agrees to pay any fees accrued. All amounts listed on {{ env('APP_NAME', 'Engagyo') }} platform are in
                    US dollars(USD).
                </p>
            </div>
        </div>
    </div>
@endsection
