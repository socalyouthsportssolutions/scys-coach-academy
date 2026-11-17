<?php
defined( 'ABSPATH' ) || exit;
return json_decode( <<<'SCYS_JSON'
{"id":"nano-1v1","title":"1v1 Nanosoccer","ages":["U6","U8","U10"],"themes":["dribbling","stealing","scoring"],"minutes":"5 minute games (source)","players":"1v1, no goalkeeper; parallel games possible (source)","equipment":"2 goals per duel; proposed balls, cones, pinnies.","setup":"Separate parallel spaces and mark retreat line. Dimensions unspecified.","instructions":["Play 1v1 with two goals, no keeper.","Use dribble-ins instead of throw-ins.","After goal or missed shot, opponent retreats behind marked line.","Player with ball chooses how to attack."],"support":["Proposed option: Use three-second restart headstart (2026 guide page 25)."],"challenge":["Proposed option: Invite a new scoring route; proposed challenge."],"observe":["Does child have time to look before pressure?","Can child choose to protect, turn or attack space?"],"sourceRefs":[{"sourceId":"guide-9","page":45}],"adaptationsProposed":true}
SCYS_JSON
, true, 512, JSON_THROW_ON_ERROR );
