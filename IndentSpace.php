<?php

namespace Dcc\IndentSpace;

use Exception;
use ExternalModules\ExternalModules;
use REDCap;
use Form;
use RCView;

class IndentSpace extends \ExternalModules\AbstractExternalModule
{
    private $indentSize;
    private $js;

    protected static $Tags = array(
        '@SPACE-LEFT' => array('description'=>'Indent Space EM<br>Use to indent a block to the right.  Values are 1-9. Example: @space-left=2'),
    );


    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     */
    public function redcap_data_entry_form(int $project_id, string $record = NULL, string $instrument, int $event_id, int $group_id = NULL, int $repeat_instance = 1)
    {
        $this->jsController($project_id, $instrument);
        echo $this->js;

    }

    public function redcap_survey_page(int $project_id, string $record = NULL, string $instrument, int $event_id, int $group_id = NULL, int $repeat_instance = 1)
    {
        $this->jsController($project_id, $instrument);
        echo $this->js;
    }

    /**
     * Augment the action_tag_explain content on project Design pages by
     * adding some additional tr following the last built-in action tag.
     * @param int $project_id
     */
    public function redcap_every_page_before_render(int $project_id) {
        if (PAGE==='Design/action_tag_explain.php') {
            global $lang;
            $lastActionTagDesc = end(\Form::getActionTags());

            // which $lang element is this?
            $langElement = array_search($lastActionTagDesc, $lang);

            foreach (static::$Tags as $tag => $tagAttr) {
                $lastActionTagDesc .= "</td></tr>";
                $lastActionTagDesc .= $this->makeTagTR($tag, $tagAttr['description']);
            }
            $lang[$langElement] = rtrim(rtrim(rtrim(trim($lastActionTagDesc), '</tr>')),'</td>');
        }
    }

    private function jsController($project_id, $instrument): void
    {

        $this->setIndentSize();
        $this->setJS($project_id, $instrument);
    }


    private function setIndentSize(): void
    {
        $this->indentSize = (int)$this->getSystemSetting('indent-size');
        if ($this->indentSize < 1 || $this->indentSize > 60 || is_null($this->indentSize)) {
            $this->indentSize = 30;
        }
    }

    private function setJS($project_id, $instrument): void
    {
        $this->js = '<script type="text/javascript">' . PHP_EOL .
            'var DCCIndentSpace = {};' . PHP_EOL .
            'DCCIndentSpace.initialize = ' .
            $this->setElementsJs($project_id, $instrument) .
            'DCCIndentSpace();' . PHP_EOL .
            '</script>' . PHP_EOL;
    }


    /**
     * Creates a single JavaScript for all elements that have the action tag "@SPACE-LEFT=".  The Step value is
     * how much each element will be indented.
     *
     * @param string $instrument instrument to look in
     * @throws Exception
     */
    private function setElementsJs($project_id, string $instrument): string
    {

        // Get the metadata with applied filters
        $q = REDCap::getDataDictionary($project_id, 'json', false, null, $instrument);

        $metadata = json_decode($q, true);
        $tag = '@SPACE-LEFT=';
        $js = "";
        foreach ($metadata as $field) {
            $field_annotations = $field['field_annotation'];
            $pos = strpos($field_annotations, $tag);
            if ($pos === false) {
                continue;
            }
            if ($field['matrix_group_name'] === "") {
                $elementTarget = $field['field_name'] . '-tr").firstElementChild';
            } else {
                $elementTarget = 'label-' . $field['field_name'] . '")';
                echo $field['field_name'] . ' is in a matrix';
            }
            $value = (int)substr($field_annotations, $pos + length($tag), 1);
            if (!$value || $value === 0) {
                continue;
            }
            $value *= $this->indentSize;
            echo $field['field_name'] . ': ' . $pos . $field_annotations . " value: " . $value . "<br>";
            $js .= PHP_EOL .
                'document.getElementById("' .
                $elementTarget .
                '.style.paddingLeft="' .
                $value .
                'px";' .
                PHP_EOL;
        }

        return $js;
    }

    /**
     * Make a table row for an action tag copied from
     * v8.5.0/Design/action_tag_explain.php
     * @param string $tag
     * @param string $description
     * @return string
     *@global integer $isAjax
     */
    protected function makeTagTR(string $tag, string $description) {
        global $isAjax, $lang;

        return \RCView::tr(array(),
            \RCView::td(array('class'=>'nowrap', 'style'=>'text-align:center;background-color:#f5f5f5;color:#912B2B;padding:7px 15px 7px 12px;font-weight:bold;border:1px solid #ccc;border-bottom:0;border-right:0;'),
                ((!$isAjax || (isset($_POST['hideBtns']) && $_POST['hideBtns'] == '1')) ? '' :
                    \RCView::button(array('class'=>'btn btn-xs btn-rcred', 'style'=>'', 'onclick'=>"$('#field_annotation').val(trim('".js_escape($tag)." '+$('#field_annotation').val())); highlightTableRowOb($(this).parentsUntil('tr').parent(),2500);"), $lang['design_171'])
                )
            ) .
            \RCView::td(array('class'=>'nowrap', 'style'=>'background-color:#f5f5f5;color:#912B2B;padding:7px;font-weight:bold;border:1px solid #ccc;border-bottom:0;border-left:0;border-right:0;'),
                $tag
            ) .
            \RCView::td(array('style'=>'font-size:12px;background-color:#f5f5f5;padding:7px;border:1px solid #ccc;border-bottom:0;border-left:0;'),
                '<i class="fas fa-cube mr-1"></i>'.$description
            )
        );

    }
}
