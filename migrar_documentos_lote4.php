<?php
/**
 * migrar_documentos_lote4.php
 * Inserta 2 documentos legales derivados del PDF 20 (que trae 2 docs en 1):
 *   (20a) Full Consent Package (English)
 *   (20b) HIPAA Privacy Practices Notice (English)
 * Idempotente por título. SISTEMA-only, POST-driven.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración docs lote 4</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Migrar documentos legales — Lote 4</h4>
<p class="text-muted">Inserta / actualiza <b>(20a) Full Consent Package (English)</b> y
<b>(20b) HIPAA Privacy Practices Notice (English)</b>.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar migración</button>
<a class="btn btn-link" href="gestionar_documentos.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$documentos = [];

// (20a) Full Consent Package (English)
$documentos[] = [
    'titulo' => '(20a) Full Consent Package (English)',
    'contenido' => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#2b2b2b;line-height:1.4;">
<h4 style="color:#5a2d82;margin:0 0 10px 0;">Full Consent Package — SRoss Nutrition PLLC</h4>

<p><b>ID:</b> {{cedula}} &nbsp; <b>Name:</b> {{paciente}} &nbsp; <b>DOB:</b> {{fecha_nacimiento}}</p>

<p>Below you will find detailed information regarding your rights and responsibilities and established policies of this practice. Please read this carefully and select the checkbox at the end of each section if you agree. Please feel free to ask any questions for clarification.</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Agreement to Use Electronic Signatures and Electronic Documents</h5>
<p>You agree that the electronic signatures included in this notice are intended to authenticate this writing and to have the same force and effect as manual signatures.</p>
<p><i>Electronic signature</i> means any electronic sound, symbol or process attached to or logically associated with a record and executed and adopted by a party with the intent to sign such record, including (without limitation) typing a name or clicking a check box.</p>
<p>You agree to use electronic documents, notices and contacts "electronic documents", for all future transactions and communications. Electronic documents contain the same information as paper documents, notices and contracts. Paper documents, notices and contracts are available at your request. If you give your consent to use electronic documents, you can later change your mind and request a paper agreement instead.</p>
<p><b>I agree:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Appointments</h5>
<p>I agree to keep all scheduled appointments and be on time. If I cannot attend a scheduled session, I will call to cancel and/or reschedule. There will be no fee if phone message or conversation is received before 24 hours of the scheduled appointment time. I understand if I miss or cancel with less than 24 hours of notice, then I will be charged for $30, and if I don't show up or call I will be liable to <b>full price</b> of the appointment.</p>
<p><b>I agree:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Financial Policy</h5>
<p>SRoss Nutrition PLLC is not a preferred provider on any insurance networks or for Medicare. All services must be paid for at time of render. A superbill can be provided at your request for you to seek reimbursement. We accept cash, check, credit cards and debit.</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Credit Card Authorization</h5>
<p>Credit Card Authorization Form For SROSS NUTRITION PLLC. Please complete all fields. You may cancel this authorization at any time by contacting us. This authorization will remain in effect until cancelled.</p>
<table style="border-collapse:collapse;width:100%;margin-bottom:8px;font-size:12px;" border="1" cellpadding="6">
<tr><td><b>Credit Card Information</b></td></tr>
<tr><td><b>Card Type:</b> ☐ MasterCard &nbsp; ☐ VISA &nbsp; ☐ Discover &nbsp; ☐ AMEX &nbsp; ☐ Other</td></tr>
<tr><td><b>Cardholder Name (as shown on card):</b> ______________________</td></tr>
<tr><td><b>Card Number:</b> ______________________</td></tr>
<tr><td><b>Expiration Date (mm/yy):</b> ______________ &nbsp; <b>CVV:</b> ________</td></tr>
<tr><td><b>Cardholder ZIP Code (from credit card billing address):</b> ______________________</td></tr>
</table>
<p>I, above for agreed upon purchases. I understand that my information will be saved to file for future transactions on my account. <b>Customer Signature:</b> ______ &nbsp; <b>Date:</b> {{fecha}}</p>
<p><b>I agree:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">HIPAA Privacy Policies</h5>
<p>The HIPAA Privacy Practices Notice is supplied separately. Please review it before signing below.</p>
<p><b>I agree:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Consent to Treatment</h5>
<p>I have read through all the above information and have been clearly advised of my rights and responsibilities as a client of <b>SRoss Nutrition PLLC</b>, including the HIPAA Notice of Privacy Practices.</p>
<p>I understand these rights and responsibilities and agree to abide by them. I consent to treatment, and I understand I have a right to receive a copy of this form upon request. I also understand that I can withdraw this consent in writing and terminate at any time.</p>
<p><b>I agree:</b> ______</p>

<h5 style="color:#5a2d82;margin:14px 0 6px 0;">Signature</h5>
<p><i>Please sign below if you agree to all policies described above.</i></p>
<p><b>Name:</b> {{paciente}}<br>
<b>Date of birth:</b> {{fecha_nacimiento}}</p>
</div>
HTML
];

// (20b) HIPAA Privacy Practices Notice (English)
$documentos[] = [
    'titulo' => '(20b) HIPAA Privacy Practices Notice (English)',
    'contenido' => <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#2b2b2b;line-height:1.4;">
<h4 style="color:#5a2d82;margin:0 0 10px 0;">HIPAA Notice of Privacy Practices — SRoss Nutrition PLLC</h4>

<p><b>ID:</b> {{cedula}} &nbsp; <b>Name:</b> {{paciente}} &nbsp; <b>DOB:</b> {{fecha_nacimiento}}</p>
<p><b>Effective Date:</b> ______</p>

<p><i>This notice describes how protected health information about you may be used and disclosed and how you can get access to this information. Please review this carefully.</i></p>

<h6 style="color:#5a2d82;">Our Pledge Regarding Protected Health Information</h6>
<p>We understand that protected health information about you and your health is personal. We are committed to protecting health information about you. This Notice applies to all records of your care generated by SRoss Nutrition PLLC personnel or your physician.</p>
<p>This Notice will tell you about the ways in which we may use or disclose protected health information about you. We also describe your rights and certain obligations we have regarding the use and disclosure of protected health information. Federal law requires us to:</p>
<ul>
<li>Make sure that protected health information that identifies you is kept private;</li>
<li>Notify you about how we protect protected health information about you;</li>
<li>Explain how, when, and why we use and disclose protected health information; and</li>
<li>Follow the terms of the Notice that is currently in effect.</li>
</ul>
<p>We are required to follow the procedures in this Notice. We reserve the right to change the terms of this Notice and to make new Notice provisions effective for all protected health information that we maintain by:</p>
<ul>
<li>Posting the revised Notice in our office;</li>
<li>Making copies of the revised Notice available upon request; and</li>
<li>Posting the revised Notice on our Website.</li>
</ul>

<h6 style="color:#5a2d82;">How We May Use and Disclose Protected Health Information About You</h6>
<p>The following categories describe different ways that we may use and disclose protected health information without your written authorization.</p>

<p><b>For Treatment.</b> We may use protected health information about you to provide you with, coordinate, or manage your medical treatment or services. We may disclose protected health information about you to doctors, nurses, technicians, medical students, or other personnel, including persons outside of our office who are involved in your medical care.</p>
<p>Staff may also share protected health information about you in order to coordinate your care for such reasons as prescriptions, lab work, and x-rays.</p>

<p><b>For Health Care Operations.</b> We may use and disclose protected health information about you for health care operations, such as our quality assessment and improvement activities, case management, coordination of care, business planning, customer service, and other activities. These uses and disclosures are necessary to run the facility, reduce health care costs, and make sure that all of our clients receive quality care. For example, we use HIPAA compliant web-based practice management and electronic medical record (EMR) Kalix for appointment scheduling, electronic record keeping and filing, electronic paperwork and coordination of care. Your protected health information is recorded, stored and transmitted in Kalix in an encrypted state. We may also combine protected health information about many clients to decide what additional services should be offered, what services are not needed, and whether certain treatments are effective.</p>

<p>Subject to applicable state law, the law allows or requires us to use or disclose your health information without your authorization in some limited situations for purposes beyond treatment, payment, and operations.</p>

<p><b>As Required by Law.</b> We will disclose protected health information about you when required to do so by federal, state, or local law.</p>

<p><b>Research.</b> We may disclose your protected health information to researchers when their research has been approved by an institutional review board or privacy board that has reviewed the research proposal and established protocols to ensure the privacy of your information.</p>

<p><b>To Avert a Serious Threat to Health or Safety.</b> We may use and disclose protected health information when necessary to prevent a serious threat to your health and safety or the health and safety of the public or another person.</p>

<p><b>Judicial and Administrative Proceedings.</b> We may disclose your protected health information in response to a court or administrative order, subpoena, discovery request, or other lawful process by someone else involved in the dispute.</p>

<p><b>Business Associates.</b> We may disclose information to business associates who perform services on our behalf, including our EMR and practice management solution Kalix and clearinghouse Office Ally. However, we require that these associates appropriately safeguard your information.</p>

<p><b>Public Health.</b> As required by law, we may disclose your protected health information to public health or legal authorities charged with preventing or controlling disease, injury, or disability.</p>

<p><b>Health Oversight Activities.</b> We may disclose protected health information to a health oversight agency for activities authorized by law.</p>

<p><b>Law Enforcement.</b> We may release protected health information as required by law, or in response to an order or warrant of a court, a subpoena, or an administrative request.</p>

<p><b>Organ and Tissue Donation.</b> If you are an organ donor, we may release protected health information to an organ donation bank or to organizations that handle organ procurement or organ, eye, or tissue transplantation.</p>

<p><b>Special Government Functions.</b> If you are a member of the armed forces, we may release protected health information about you if it relates to military and veterans activities. We may also release your protected health information for national security and intelligence purposes, protective services for the President, and medical suitability or determinations made by the Department of State.</p>

<p><b>Coroners, Medical Examiners, and Funeral Directors.</b> We may release protected health information to a coroner or medical examiner.</p>

<p><b>Correctional Institutions and Other Law Enforcement Custodial Situations.</b> If you are an inmate of a correctional institution or under the custody of a law enforcement official, we may release protected health information about you to the correctional institution or law enforcement official as necessary for your or another person's health and safety.</p>

<p><b>Worker's Compensation.</b> We may disclose protected health information as necessary to comply with laws relating to worker's compensation or other similar programs established by law.</p>

<p><b>Food and Drug Administration (FDA).</b> We may disclose to the FDA, or persons under the jurisdiction of the FDA, protected health information relative to adverse events with respect to drugs, foods, supplements, products, and product defects.</p>

<p><b>Fundraising and/or Marketing Communications.</b> We may contact you about fundraising activities or to market health-related services or benefits. You have the right to opt-out of this type of communication by contacting us. SRoss Nutrition PLLC will not sell your information to any third party.</p>

<p><b>Appointment Reminders, Treatment Alternatives, and Health-Related Benefits and Services.</b> We may use and disclose protected health information to contact you (by email, telephone, voice message and/or text (SMS) message) as a reminder that you have an appointment for treatment or medical care. We may use and disclose protected health information to tell you about or recommend possible treatment options, treatment alternatives, or health-related benefits or services that may be of interest to you.</p>

<p><b>For Work-Related Injuries or Illnesses or Workplace Medical Surveillance.</b> We may disclose health care information where your employer has a duty under state or federal law, to keep records or act on such information.</p>

<p><b>Incidental Disclosures</b> may occur as a by-product of permitted uses and disclosures of your health care information. These incidental disclosures are permitted if we have applied reasonable safeguards to protect the confidentiality of your health care information.</p>

<h6 style="color:#5a2d82;">Electronic Medical Record</h6>
<p>To promote quality care, SRoss Nutrition PLLC operates an electronic medical record (EMR) Kalix. Providers and some providers unaffiliated with the practice may have access to the EMR. Your medical record may be comprised of information in the EMR as well as in a paper record. SRoss Nutrition PLLC is legally obligated to notify any individual whose protected health information is affected by a security breach.</p>

<h6 style="color:#5a2d82;">You Can Object to Certain Uses and Disclosures</h6>
<p>Unless you object, or request that only a limited amount or type of information be shared, we may use or disclose protected health information about you in the following circumstances:</p>
<p>We may share with a family member, relative, friend or other person identified by you protected health information that is directly relevant to that person's involvement in your care or payment for your care.</p>
<p>We may share protected health information with a public or private agency (such as the American Red Cross) for disaster relief purposes.</p>

<h6 style="color:#5a2d82;">Your Rights Regarding Protected Health Information About You</h6>
<p>You have the following rights regarding protected health information that we maintain about you:</p>

<p><b>Right to Inspect and Copy.</b> You have the right to inspect and copy protected health information that may be used to make decisions about your care or payment for your care, including protected health information stored electronically. You can request that we provide access in an electronic format that is readily producible.</p>
<p>To inspect and copy protected health information, you must submit your request in writing. If you request a copy of the information, we may charge a fee for the costs of copying, mailing, or supplies associated with your request. We will respond to your request no later than 30 days after we receive it.</p>

<p><b>Right to Amend.</b> If you feel that protected health information we have about you is incorrect or incomplete, you may ask us to amend or supplement the information. To request an amendment, your request must be made in writing. We will act on your request for an amendment no later than 60 days after we receive it.</p>

<p><b>Right to an Accounting of Disclosures.</b> You have the right to request an "accounting of disclosures." This is a list of the disclosures we made of protected health information about you. To request this list, you must submit your request in writing. The first list you request within a 12-month period will be free.</p>

<p><b>Right to Request Restrictions.</b> You have the right to request a restriction or limitation on the protected health information we use or disclose about you for treatment, payment, or health care operations, or to persons involved in your care.</p>

<p><b>Right to Request Confidential Communications.</b> You have the right to request that we communicate with you about medical matters in a certain way or at a certain location.</p>

<p><b>Right to a Paper Copy of This Notice.</b> You have the right to a paper copy of this Notice at any time even if you have agreed to receive it electronically.</p>

<p><b>Right to Receive Notice of Breach.</b> You have a right to be notified upon a breach of any of your unsecured protected health information.</p>

<p><b>Rights for Out-of-Pocket Payments.</b> If you paid out of pocket in full for a specific item or service, you have a right to ask that your protected health information with respect to that item or service not be disclosed to a health plan for purposes of payment or health care operations.</p>

<h6 style="color:#5a2d82;">Types of Uses and Disclosures Requiring an Authorization</h6>
<p>Most uses and disclosures of psychotherapy notes require us to obtain an authorization from you. In addition, in most instances, we cannot use or disclose your protected health information for marketing purposes or sell your protected health information without your written authorization. Finally, any other use or disclosure not described in this Notice will be made only with your authorization. Any time you provide us with a written authorization, you may revoke it any time in writing.</p>

<h6 style="color:#5a2d82;">Other Uses and Disclosures</h6>
<p>We will obtain your written authorization before using or disclosing your protected health information for purposes other than those described in this Notice (or as otherwise permitted or required by law).</p>

<h6 style="color:#5a2d82;">You May File a Complaint About Our Privacy Practices</h6>
<p>If you believe your privacy rights have been violated, you may file a complaint with us or file a written complaint with the Secretary of the Department of Health and Human Services. A complaint to the Secretary should be filed within 180 days of the occurrence or action that is the subject of the complaint.</p>
<p>If you file a complaint, we will not take any action against you or change our treatment of you in any way.</p>

<h6 style="color:#5a2d82;">Changes to This Notice</h6>
<p>We reserve the right to change this Notice and make the new Notice apply to health information we already have, as well as any information we receive in the future. We will post a copy of our current Notice in our office. The notice will have the effective date clearly marked at the top of the first page.</p>

<h6 style="color:#5a2d82;">Acknowledgement of Privacy Notice</h6>
<p>I acknowledge that I have received a copy of the HIPAA Privacy Practices Notice on this day.</p>
<p><b>Name:</b> {{paciente}}<br>
<b>Date of birth:</b> {{fecha_nacimiento}}</p>
</div>
HTML
];

$msgs = [];
foreach ($documentos as $d) {
    $chk = $conexion->prepare("SELECT id_documento FROM documentos WHERE titulo = ? LIMIT 1");
    $chk->bind_param('s', $d['titulo']);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();
    if ($row) {
        $up = $conexion->prepare("UPDATE documentos SET contenido = ? WHERE id_documento = ?");
        $up->bind_param('si', $d['contenido'], $row['id_documento']);
        $msgs[] = $up->execute()
            ? 'ACTUALIZADO (id '.(int)$row['id_documento'].'): '.$d['titulo']
            : 'ERROR: '.$up->error;
        $up->close();
    } else {
        $ins = $conexion->prepare("INSERT INTO documentos (titulo, contenido, archivo_pdf, estado) VALUES (?, ?, NULL, 1)");
        $ins->bind_param('ss', $d['titulo'], $d['contenido']);
        $msgs[] = $ins->execute()
            ? 'CREADO (id '.(int)$conexion->insert_id.'): '.$d['titulo']
            : 'ERROR: '.$ins->error;
        $ins->close();
    }
}

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migración</title>'
   . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
   . '<body class="p-4"><div class="container" style="max-width:720px;"><h4>Resultado — Documentos lote 4</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item">'.htmlspecialchars($m).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="gestionar_documentos.php">Ir a Documents</a></div></body></html>';
