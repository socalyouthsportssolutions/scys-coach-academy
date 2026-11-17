<?php
defined( 'ABSPATH' ) || exit;
return json_decode( <<<'SCYS_JSON'
{"id":"build-up","title":"Build Up","ages":["U8","U10","U12"],"themes":["changing-numbers","support","decisions"],"minutes":"3 rounds of 5–6 minutes (source)","players":"2 teams; build from 1v0, 1v1 or 2v1 (source)","equipment":"Proposed: ball, goals, cones, pinnies.","setup":"Start players at goallines; dimensions unspecified.","instructions":["Begin with chosen starting numbers.","After a goal, a player joins the conceding team from its goalline.","Once everyone enters, play a five-minute game.","Swap starting points and repeat."],"support":["Proposed option: Use timed entry when no goals occur (source suggestion) to reduce waiting."],"challenge":["Proposed option: Start a joining player at opposite end to change available space (source progression)."],"observe":["Does child notice new teammate or opponent?","How long does a child wait outside play?"],"sourceRefs":[{"sourceId":"guide-8","page":41}],"adaptationsProposed":true}
SCYS_JSON
, true, 512, JSON_THROW_ON_ERROR );
