<?php
/**
 * kkm
 * Test data installer
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);

if (!$has_permission) {
	echo('<div class="kkm_error_message">'.__('You don\'t have permissions to view this page.', 'kkm').'</div>');
} else {

$data = <<<EOS
    Lorem ipsum dolor sit amet, consectetur adipiscing elit.
    Morbi ut est risus, in dignissim ligula.
    Integer molestie scelerisque metus, rhoncus sodales metus tempus a.
    Integer adipiscing porta lacus, at congue arcu hendrerit in.
    Donec gravida odio sed ante sagittis at ullamcorper nulla posuere.
    Sed fringilla lacinia elit, nec dignissim tortor accumsan fermentum.

    Maecenas molestie mauris a dui ullamcorper condimentum.
    Phasellus at velit non nisi elementum varius.
    Suspendisse ut ipsum ac tortor eleifend tempor.
    Nulla convallis tristique est, vitae imperdiet erat vulputate eu.

    Ut rhoncus cursus mi, sit amet porttitor arcu bibendum et.
    In at nunc a elit pellentesque condimentum.
    Aenean sed velit sem, non malesuada enim.
    Sed ut lacus leo, sit amet tristique augue.

    Proin dignissim magna id dui dapibus a vulputate orci lacinia.
    Quisque tempor arcu vitae tellus ultricies eget varius orci gravida.

    Sed scelerisque nisl fringilla ligula aliquet interdum.
    Etiam commodo vehicula leo, id lacinia tortor convallis at.
    Nunc pretium diam sit amet enim pellentesque et tempus sapien dictum.
    Maecenas et mauris tellus, eu tincidunt nibh.
    Nunc gravida velit ut augue porta consectetur.
    Praesent sit amet sem urna, ullamcorper bibendum odio.

    Aliquam facilisis odio id eros feugiat molestie sed viverra arcu.

    Suspendisse faucibus mauris hendrerit odio lacinia eu tempor augue mollis.
    Mauris ut massa placerat arcu tempus posuere.
    Vestibulum id ante id sapien pulvinar molestie.
    Donec ultricies erat ornare enim vehicula accumsan non a magna.
    Nunc vitae erat a elit pulvinar eleifend.

    Vestibulum non leo non sapien sagittis rutrum sed nec mi.

    Aliquam viverra augue at tellus ultrices consectetur.
    Duis eu nibh quam, mollis condimentum sem.

    Fusce eget dolor leo, euismod semper massa.
    Fusce eget nibh viverra massa imperdiet adipiscing sit amet in dolor.
    In sed nulla egestas magna viverra egestas non non ligula.
EOS;
	
	$items = preg_split('/(\r|\n)+\s*/', $data);
	foreach ($items as $item) {
		$i = rand(1, 5);
		if (preg_match('/(?:\w+ ){'.$i.'}/', $item, $matches)) {
			$title = trim($matches[0]);
			
			$wpdb->query(
				$wpdb->prepare(
					'insert into `kkm_compositions` (`title`) values (%s);',
					$title
				)
			);
		}
	}
}
?>