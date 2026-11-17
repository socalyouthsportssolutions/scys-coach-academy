<?php
defined( 'ABSPATH' ) || exit;
return json_decode( <<<'SCYS_JSON'
{"id":"home-away","title":"Home & Away","ages":["U6","U8","U10"],"themes":["keeping","stealing","space"],"minutes":"3–4 minute rounds (source)","players":"2 teams; sizes not specified","equipment":"Proposed: several balls, cones, two colours of pinnies.","setup":"Divide area into two team halves. Balls start on halfway.","instructions":["Each team tries to keep most balls under control in its half.","At the end, count one point per ball under control in your half.","Restart and change sides as appropriate."],"support":["Proposed option: Use more balls to offer more routes into the game."],"challenge":["Proposed option: Try fewer balls so players have a reason to cooperate (source progression)."],"observe":["Who rarely reaches a ball?","Does the child look for safe space to keep possession?"],"sourceRefs":[{"sourceId":"guide-8","page":32}],"adaptationsProposed":true}
SCYS_JSON
, true, 512, JSON_THROW_ON_ERROR );
