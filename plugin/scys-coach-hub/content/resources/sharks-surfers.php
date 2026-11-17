<?php
defined( 'ABSPATH' ) || exit;
return json_decode( <<<'SCYS_JSON'
{"id":"sharks-surfers","title":"Sharks & Surfers","ages":["U6","U8","U10"],"themes":["dribbling","protecting","stealing","scoring"],"minutes":"3–4 minute rounds (source)","players":"1–2 sharks; surfers each with a ball (source)","equipment":"Proposed: balls, cones, goals or gates, pinnies.","setup":"Beaches at the ends and ocean in the middle, as shown in the original diagram. Dimensions unspecified.","instructions":["Surfers dribble and try to score.","Sharks steal and keep a ball; three seconds of possession earns a shark point.","Rotate roles so children experience both aims. Rotation method to be agreed."],"support":["Proposed option: Give a surfer a clear starting beach and reduce immediate pressure."],"challenge":["Proposed option: Ask a ready player to score from the ocean only (also suggested on source card)."],"observe":["Does the child choose to dribble, protect or turn?","After stealing, does the shark try to keep possession?"],"sourceRefs":[{"sourceId":"guide-8","page":31}],"adaptationsProposed":true,"mediaId":"sharks"}
SCYS_JSON
, true, 512, JSON_THROW_ON_ERROR );
