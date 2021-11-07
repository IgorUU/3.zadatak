<?php

namespace Drupal\zadatak3\Form;

use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Zadatak3Form extends FormBase
{
  public function getFormId()
  {
    return 'zadatak3';  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $validators = [
      'file_validate_extensions' => ['csv']
    ];

    $form['csv_file'] = [
      '#type' => 'managed_file',
      '#name' => 'csv_file',
      '#title' => 'File *',
      '#size' => 20,
      '#description' => 'Excel format only',
      '#upload_validators' => $validators,
      '#upload_location' => 'public://content/excel_files/'
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
      '#button_type' => 'Primary'
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    if ($form_state->getValue('csv_file') == NULL) {
      $form_state->setErrorByName('csv_file', 'upload proper file');
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $file = \Drupal::entityTypeManager()->getStorage('file')
      ->load($form_state->getValue('csv_file')[0]);

    $full_path = $file->get('uri')->value;
    $file_name = basename($full_path);

    //POGLEDAJ OVDE DA LI BI UMESTO OVOG DELA: realpath('public://content/excel_files/'.$file_name)   MOGLO DA IDE OVAKO:  ($full_path) 
    $inputFileName = \Drupal::service('file_system')->realpath('public://content/excel_files/'.$file_name);

    $spreadsheet = IOFactory::load($inputFileName);

    $sheetData = $spreadsheet->getActiveSheet();

    $rows = [];

    foreach($sheetData->getRowIterator() as $row) {
      $cellIterator = $row->getCellIterator();
      $cellIterator->getIterateOnlyExistingCells(FALSE);
      $cells = [];
      foreach($cellIterator as $cell) {
        $cells[] = $cell->getValue();
      }
      $rows[] = $cells;
    }

    array_shift($rows);

    foreach($rows as $row) {
      $values = \Drupal::entityQuery('node')->condition('title', $row[0])->execute();
      $node_not_exists = empty($values);

      //Generisanje slike
      $data = file_get_contents($row[1]);
      $slika = file_save_data($data, 'public://sample.png', FileSystemInterface::EXISTS_RENAME);
      dsm($slika);
      
      if($node_not_exists){
        //Znači, ako node $values ne postoji (Ako nema 'title' field isti kao $row[0]), onda pravimo novi node sa tom vrednošću ($row[0])
        $node = \Drupal::entityTypeManager()->getStorage('node')->create([
          'type' => 'clanci_o_programiranju',
          'title' => $row[0],
          'field_imagee' => [
            'target_id' => $slika->id(),
            'alt' => 'Sample',
            'title' => 'Sample File',
            'width' => '80',
            'height' => '50'
          ],
          'field_description' => $row[2],
          'field_link_to_we' => 'http://www.'.$row[3],
        ]);
        $node->save();
      } else {
        $nid = reset($values);

        $node = Node::load($nid);
        $node->setTitle($row[0]);
        $node->set('field_imagee', [
          'target_id' => $slika->id(),
          'alt' => 'Sample',
          'title' => 'Sample File',
          'width' => '80',
          'height' => '50'
        ]);
        $node->set('field_description', $row[2]);
        $node->set('field_link_to_we', $row[3]);
        $node->save();
      }
    }

    \Drupal::messenger()->addMessage('imported succesfully');

  }
}
