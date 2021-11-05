<?php

namespace Drupal\zadatak3\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
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

  }
}
