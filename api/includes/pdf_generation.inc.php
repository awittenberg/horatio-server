<?php

include("includes/mpdf60/mpdf.php");

/*
 * Create a new instance of mPDF, using an 11-point font.
 */
$mpdf=new mPDF('', '', 11); 

/*
 * Enable the use of imported PDFs.
 */
$mpdf->SetImportUse(); 

/*
 * Load the SBE's blank form as our template.
 */
$mpdf->SetSourceFile('includes/application.pdf');
$template = $mpdf->ImportPage(1);
$mpdf->UseTemplate($template);

$form = json_decode(file_get_contents('includes/application-map.json'));
#$values = json_decode(file_get_contents('includes/ballot-completed.json'));

/*
 * Iterate through every section of the form field.
 */
foreach ($form as $section_name => $section)
{

	/*
	 * Iterate through every field in this section, if it's broken into sections.
	 */
	if (!isset($section->coordinates))
	{

		foreach ($section as $field_name => $field)
		{

			/*
			 * All manner of special values trip up mPDF, so make sure 
			 */
			$value = utf8_encode($values->$section_name->$field_name);

			/*
			 * Convert data to the format in which it needs to appear on the form.
			 */
			if ( ($section_name == 'signature') && ($field_name == 'date') )
			{
				$value = date('m d y', strtotime($value));
			}
			elseif ( ($section_name == 'change') && ($field_name == 'date_moved') )
			{
				$value = date('m d y', strtotime($value));
			}
			elseif ( ($section_name == 'election') && ($field_name == 'date') )
			{
				$value = date('m d y', strtotime($value));
			}
			elseif ( ($section_name == 'election') && ($field_name == 'type') )
			{

				if ($value == 'Democratic Primary') $x = $field->coordinates->x + 50;
				elseif ($value == 'Republican Primary') $x = $field->coordinates->x + 89.1;
				else $x = $field->coordinates->x;
				$field->coordinates->x = $x;
				$value = 'x';

			}

			elseif ( ($section_name == 'delivery') && ($field_name == 'to') )
			{

				$value = 'mailing address';
				if ($value == 'mailing address')
				{
					$x = $field->coordinates->x + 58.3;
					$y = $field->coordinates->y + 0.1;
				}
				elseif ($value == 'email')
				{
					$x = $field->coordinates->x;
					$y = $field->coordinates->y + 4.8;
				}
				elseif ($value == 'fax')
				{
					$x = $field->coordinates->x + 58.3;
					$y = $field->coordinates->y + 4.9;
				}
				else
				{
					$x = $field->coordinates->x;
					$y = $field->coordinates->y;
				}
				$field->coordinates->x = $x;
				$field->coordinates->y = $y;
				$value = 'x';
				
			}
			elseif ( ($section_name == 'signature') && ($field_name == 'signed') )
			{

				$components = array('first', 'middle', 'last', 'suffix');
				$value = '/s/';
				foreach ($components as $component)
				{
					$value .= utf8_encode($values->name->$component) . ' ';
				}
				$value = trim($value);

			}

			$mpdf->WriteText($field->coordinates->x, $field->coordinates->y, $value);

		}

	}
	else
	{
		$value = utf8_encode($values->$section_name);
		$mpdf->WriteText($section->coordinates->x, $section->coordinates->y, $value);
	}

}

/*
 * Save the file to the applications directory.
 */
$dir = 'applications/';
$mpdf->Output($dir . 'completed.pdf', 'F');
//$mpdf->Output();
