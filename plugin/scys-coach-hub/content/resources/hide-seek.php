<?php
defined( 'ABSPATH' ) || exit;
return json_decode( <<<'SCYS_JSON'
{"id":"hide-seek","title":"Hide & Seek","ages":["U8","U10","U12"],"themes":["protecting","stealing","scoring"],"minutes":"3–4 minute rounds (source)","players":"2 teams, with 2 seekers initially (source)","equipment":"Proposed: several balls, cones, goals, pinnies.","setup":"Give hiders balls in an area; identify seekers’ scoring targets. Dimensions unspecified.","instructions":["Hiders keep balls under control and do not score.","Seekers win balls and score.","At end: one point per controlled hider ball; two per seeker goal. Change roles."],"support":["Proposed option: Give a hider more starting space."],"challenge":["Proposed option: Ask some hiders to keep a ball together (source progression)."],"observe":["Does the child protect, turn or find a teammate?","Can a seeker keep the ball after winning it?"],"sourceRefs":[{"sourceId":"guide-8","page":35}],"adaptationsProposed":true}
SCYS_JSON
, true, 512, JSON_THROW_ON_ERROR );
