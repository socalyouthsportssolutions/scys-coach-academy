<?php
defined( 'ABSPATH' ) || exit;
return json_decode( <<<'SCYS_JSON'
{"id":"rondo-switch","title":"4v2 Keep & Switch","ages":["U12","U14"],"themes":["switching","passing","support"],"minutes":"Not specified; proposed short rounds agreed with coach","players":"4 attackers and 2 defenders (source)","equipment":"Proposed: ball, cones, pinnies, counterattack targets.","setup":"Use original 4v2 arrangement. Agree what counts as a switch; dimensions unspecified.","instructions":["Keep possession and switch ball.","Each successful switch earns one point.","Defenders win and counterattack; scoring earns two.","Rotate roles."],"support":["Proposed option: Make switch destination clearer with a receiving zone; proposed setup."],"challenge":["Proposed option: Ask ready player to attract pressure before switching; proposed challenge."],"observe":["Is child switching into useful space or just satisfying rule?","Does receiver prepare next action?"],"sourceRefs":[{"sourceId":"guide-8","page":70}],"adaptationsProposed":true}
SCYS_JSON
, true, 512, JSON_THROW_ON_ERROR );
