<?php
defined( 'ABSPATH' ) || exit;
return json_decode( <<<'SCYS_JSON'
{"id":"soccer-tag","title":"Soccer tag","ages":["U6","U8","U10"],"themes":["dribbling","space","scoring"],"minutes":"3–4 minute rounds (source)","players":"1–2 taggers; other numbers flexible (source)","equipment":"Proposed: balls for non-taggers, cones, goals or gates, pinnies.","setup":"Mark an area and scoring targets. Dimensions not specified.","instructions":["Non-taggers travel with a ball and try to score.","One or two taggers try to tag players; agree safe tagging.","The source progression makes a tagged player the new tagger. Explain before using it."],"support":["Proposed option: Start with one tagger and show a clear starting place."],"challenge":["Proposed option: Invite a player to find a different route to a target."],"observe":["Does the child look for space or follow a crowd?","Does being tagged stop involvement or invite another attempt?"],"sourceRefs":[{"sourceId":"guide-8","page":30}],"adaptationsProposed":true}
SCYS_JSON
, true, 512, JSON_THROW_ON_ERROR );
