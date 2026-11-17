<?php
// Appended to the existing integration script; disposable WordPress only.
function support_api( $method, $path, $data = null, $nonce = true ) {
 $r = new WP_REST_Request( $method, '/scys-hub/v2/' . $path );
 if ( $nonce ) { $r->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) ); }
 if ( $data !== null ) { $r->set_header( 'Content-Type', 'application/json' ); $r->set_body( wp_json_encode( $data ) ); }
 return rest_do_request( $r );
}
function support_save( $data ) { return support_api( 'POST', 'support/save', $data ); }
function request_payload( $d, $fields = array(), $extra = array() ) {
 return array_merge( array( 'id' => $d['id'], 'version' => $d['version'], 'token' => wp_generate_uuid4(), 'action' => 'request', 'fields' => array_merge( array_intersect_key( $d, array_flip( array( 'seasonTeam', 'ageGroup', 'topic', 'type', 'proposedAt', 'location', 'arrangement', 'status', 'closureReason' ) ) ), $fields ) ), $extra );
}
SCYS_Hub_Mentoring::install();
wp_set_current_user( $coach );
$seed = array( 'action' => 'request', 'token' => wp_generate_uuid4(), 'fields' => array( 'seasonTeam' => 'Synthetic autumn team', 'ageGroup' => 'U8', 'topic' => 'Reduce adult explanations', 'type' => 'practice', 'proposedAt' => '2026-11-09T16:00', 'location' => 'Synthetic field', 'arrangement' => 'Coach leads, mentor watches, never alone; adult hand-back agreed.', 'status' => 'requested' ) );
verify_hub( support_save( $seed )->get_status() === 403, 'Real-data pilot collection defaults to disabled' );
update_option( 'scys_mentoring_policy', array( 'enabled' => true, 'reportingUrl' => 'https://example.org/test-only-reporting', 'approval' => 'Synthetic isolated acceptance only' ) );
verify_hub( support_api( 'POST', 'support/save', $seed, false )->get_status() === 403, 'Mutation requires CSRF nonce even in direct dispatch' );
$d = support_save( $seed )->get_data(); $rid = $d['id'];
verify_hub( $rid > 0 && $d['coachId'] === $coach, 'Request is linked to authenticated adult account' );
verify_hub( support_save( $seed )->get_data()['id'] === $rid, 'Repeated create returns the same request' );
verify_hub( support_save( request_payload( $d, array( 'status' => 'confirmed' ) ) )->get_status() === 400, 'Unaccepted request cannot be confirmed' );
$d = support_save( request_payload( $d, array( 'status' => 'arranging' ), array( 'agree' => true ) ) )->get_data();
wp_set_current_user( $mentor );
verify_hub( support_api( 'GET', 'support/' . $rid )->get_status() === 200, 'Current assigned mentor can read direct record' );
$d = support_save( request_payload( $d, array( 'status' => 'confirmed' ), array( 'accept' => true ) ) )->get_data();
verify_hub( $d['status'] === 'confirmed', 'Confirmation requires recorded coach agreement and mentor acceptance' );
$stale = request_payload( $d );
$draft = array( 'id' => $rid, 'version' => $d['version'], 'token' => wp_generate_uuid4(), 'action' => 'visit', 'fields' => array( 'type' => 'practice', 'focus' => 'Synthetic observation' ) );
$d = support_save( $draft )->get_data();
verify_hub( count( $d['visits'] ) === 1 && $d['status'] === 'confirmed', 'Partial visit saves as draft without attendance' );
verify_hub( support_save( $draft )->get_data()['version'] === $d['version'], 'Identical retry does not append another visit or history change' );
verify_hub( support_save( $stale )->get_status() === 409, 'Stale write rejected without silently overwriting newer visit' );
verify_hub( support_save( request_payload( $d, array(), array( 'coachId' => $volunteer ) ) )->get_status() === 403, 'Tampered ownership cannot reassign coach' );
verify_hub( support_save( request_payload( $d, array( 'status' => 'delivered' ) ) )->get_status() === 400, 'Past proposed date never counts as delivery' );
$visit = array( 'date' => '2026-11-09T16:00', 'type' => 'practice', 'focus' => 'Shorter explanation', 'observation1' => 'Adult talked across two restarts.', 'observation2' => 'Adult tried one short cue.', 'strength' => 'Calm delivery', 'nextAction' => 'Reuse one cue', 'resourceId' => 'hide-seek', 'followUpOwner' => 'coach', 'followUpDate' => '2026-11-09', 'result' => '' );
$vid = array_key_first( $d['visits'] );
$payload = array( 'id' => $rid, 'version' => $d['version'], 'token' => wp_generate_uuid4(), 'visitId' => $vid, 'action' => 'visit', 'fields' => $visit, 'complete' => true );
verify_hub( support_save( $payload )->get_status() === 400, 'Explicit attendance confirmation is required' );
$payload['confirmAttendance'] = true;
$d = support_save( $payload )->get_data();
verify_hub( $d['status'] === 'follow-up due' && $d['visits'][ $vid ]['attendanceBy'] === $mentor, 'Delivery retains outstanding follow-up and attendance author' );
$summary = support_api( 'GET', 'support' )->get_data();
verify_hub( $summary['records'][0]['followUps'][0]['overdue'] && ! isset( $summary['records'][0]['visits'] ), 'Due list flags overdue without returning note bodies' );
verify_hub( support_save( request_payload( $d, array( 'status' => 'closed', 'closureReason' => 'Done' ) ) )->get_status() === 400, 'Closure cannot hide an outstanding action' );
$payload['version'] = $d['version']; $payload['token'] = wp_generate_uuid4(); unset( $payload['visitId'] );
$d = support_save( $payload )->get_data();
verify_hub( count( $d['visits'] ) === 2, 'Two delivered visits remain linked without overwriting' );
$payload['version'] = $d['version']; $payload['token'] = wp_generate_uuid4(); $payload['visitId'] = $vid; $payload['fields']['result'] = 'Not yet; check again at the next practice.'; $payload['fields']['followUpState'] = 'open';
$d = support_save( $payload )->get_data();
verify_hub( $d['status'] === 'follow-up due' && SCYS_Hub_Mentoring::pending( $d ), 'Not-yet result can remain open in follow-up queue' );
wp_set_current_user( $other );
verify_hub( support_api( 'GET', 'support/' . $rid )->get_status() === 404 && count( support_api( 'GET', 'support' )->get_data()['records'] ) === 0, 'Unrelated mentor denied direct record and list' );
wp_set_current_user( $volunteer );
verify_hub( support_api( 'GET', 'support/' . $rid )->get_status() === 404 && support_save( request_payload( $d ) )->get_status() === 404, 'Another coach denied guessed record and write' );
wp_set_current_user( 1 );
verify_hub( support_api( 'GET', 'support/' . $rid )->get_status() === 200, 'Programme administrator can inspect shared record' );
update_user_meta( $coach, '_scys_mentor_id', $other );
wp_set_current_user( $mentor );
verify_hub( support_api( 'GET', 'support/' . $rid )->get_status() === 404 && support_save( request_payload( $d ) )->get_status() === 404, 'Reassignment removes former mentor read and write immediately' );
wp_set_current_user( $other );
$d = support_api( 'GET', 'support/' . $rid )->get_data();
verify_hub( $d['mentorAcceptedBy'] === 0 && count( $d['visits'] ) === 2, 'Replacement mentor must accept; historical visits remain intact' );
wp_set_current_user( $coach );
$r = support_api( 'GET', 'support/' . $rid );
verify_hub( str_contains( $r->get_headers()['Cache-Control'], 'no-store' ), 'Private detail response prohibits storage' );
verify_hub( support_save( request_payload( $d, array( 'proposedAt' => '2026-02-31T12:00' ) ) )->get_status() === 400, 'Invalid calendar date rejected without saving' );
foreach ( array_keys( $d['visits'] ) as $visit_id ) {
 $fields = $d['visits'][ $visit_id ]; $fields['result'] = 'It did not help; agreed to adapt the next practice.'; $fields['followUpState'] = 'complete';
 $d = support_save( array( 'id' => $rid, 'version' => $d['version'], 'token' => wp_generate_uuid4(), 'action' => 'visit', 'visitId' => $visit_id, 'fields' => $fields, 'complete' => true ) )->get_data();
}
verify_hub( ! SCYS_Hub_Mentoring::pending( $d ) && $d['status'] === 'delivered', 'Completing each action resolves due work without losing attendance' );
$d = support_save( request_payload( $d, array( 'status' => 'closed', 'closureReason' => 'Both visits followed up; next cycle has a revised focus.' ) ) )->get_data();
verify_hub( $d['status'] === 'closed' && count( $d['visits'] ) === 2 && ! empty( $d['history'] ), 'Closed request retains multiple visits and change history' );
$state_before = SCYS_Coach_Hub::state( $coach );
$full = wp_json_encode( SCYS_Coach_Hub::catalogue()->get_data() );
verify_hub( ! str_contains( $full, 'Adult talked across two restarts' ) && ! str_contains( wp_json_encode( SCYS_Coach_Hub::me() ), 'Synthetic autumn team' ), 'Operational notes absent from startup catalogue and learner state' );
foreach ( SCYS_Hub_Content::index()['categories'] as $category ) { if ( $category['id'] === 'coach-mentoring' ) { $mentoring_ids = $category['lessonIds']; } }
verify_hub( count( $mentoring_ids ) === 7, 'One ordered category contains seven reusable mentoring lessons' );
foreach ( $mentoring_ids as $id ) {
 $lesson = SCYS_Coach_Hub::item( 'lessons', $id );
 verify_hub( count( $lesson['quiz'] ) === 3 && strlen( $lesson['sections'][0]['html'] ) > 1200, 'Complete lesson and three scenarios: ' . $id );
 $learner = support_api( 'GET', 'lessons/' . $id )->get_data();
 verify_hub( ! isset( $learner['quiz'][0]['correct'] ) && ! isset( $learner['quiz'][0]['explanation'] ), 'Server-held answer key: ' . $id );
 api_hub( 'POST', 'quiz', array( 'lessonId' => $id, 'answers' => array_column( $lesson['quiz'], 'correct' ) ) );
 api_hub( 'POST', 'quiz', array( 'lessonId' => $id, 'answers' => array_column( $lesson['quiz'], 'correct' ) ) );
}
verify_hub( count( SCYS_Coach_Hub::state( $coach )['completed'] ) === count( $state_before['completed'] ) + 7, 'All mentoring quiz retries preserve old completion and deduplicate by lesson ID' );
$count = count( SCYS_Hub_Content::posts( 'lessons' ) );
update_option( 'scys_hub_content_version', '0.2.0' ); SCYS_Hub_Content::migrate(); SCYS_Hub_Content::migrate();
verify_hub( count( SCYS_Hub_Content::posts( 'lessons' ) ) === $count && count( support_api( 'GET', 'support/' . $rid )->get_data()['visits'] ) === 2, 'Idempotent migration preserves content and operational visits' );
$subscriber = wp_insert_user( array( 'user_login' => 'support-subscriber', 'user_pass' => wp_generate_password( 24 ), 'role' => 'subscriber' ) );
foreach ( array( $subscriber, 0 ) as $denied ) { wp_set_current_user( $denied ); verify_hub( support_api( 'GET', 'support' )->get_status() >= 400 && support_api( 'GET', 'support/' . $rid )->get_status() >= 400 && support_save( $seed )->get_status() >= 400, 'Unprivileged or signed-out access denied: ' . $denied ); }
echo "ALL MENTORING INTEGRATION CHECKS PASSED\n";
