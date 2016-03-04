<?php
/*
 * Spring Signage Ltd - http://www.springsignage.com
 * Copyright (C) 2015 Spring Signage Ltd
 * (DataSetColumn.php)
 */


namespace Xibo\Controller;


use Xibo\Exception\AccessDeniedException;

class DataSetColumn extends Base
{
    /**
     * Column Page
     * @param $dataSetId
     */
    public function displayPage($dataSetId)
    {
        $dataSet = $this->getFactoryService()->get('DataSetFactory')->getById($dataSetId);

        if (!$this->getUser()->checkEditable($dataSet))
            throw new AccessDeniedException();

        $this->getState()->template = 'dataset-column-page';
        $this->getState()->setData([
            'dataSet' => $dataSet
        ]);
    }

    /**
     * Column Search
     * @param $dataSetId
     *
     * @SWG\Get(
     *  path="/dataset/{dataSetId}/column",
     *  operationId="dataSetColumnSearch",
     *  tags={"dataset"},
     *  summary="Search Columns",
     *  description="Search Columns for DataSet",
     *  @SWG\Parameter(
     *      name="dataSetId",
     *      in="path",
     *      description="The DataSet ID",
     *      type="integer",
     *      required=true
     *   ),
     *  @SWG\Response(
     *      response=200,
     *      description="successful operation",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/DataSetColumn")
     *      )
     *  )
     * )
     */
    public function grid($dataSetId)
    {
        $dataSet = $this->getFactoryService()->get('DataSetFactory')->getById($dataSetId);

        if (!$this->getUser()->checkEditable($dataSet))
            throw new AccessDeniedException();

        $dataSetColumns = $this->getFactoryService()->get('DataSetColumnFactory')->getByDataSetId($dataSetId);

        foreach ($dataSetColumns as $column) {
            /* @var \Xibo\Entity\DataSetColumn $column */

            $column->dataType = __($column->dataType);
            $column->dataSetColumnType = __($column->dataSetColumnType);

            if ($this->isApi())
                break;

            $column->includeProperty('buttons');

            // Edit
            $column->buttons[] = array(
                'id' => 'dataset_button_edit',
                'url' => $this->urlFor('dataSet.column.edit.form', ['id' => $dataSetId, 'colId' => $column->dataSetColumnId]),
                'text' => __('Edit')
            );

            if ($this->getUser()->checkDeleteable($dataSet)) {
                // Delete
                $column->buttons[] = array(
                    'id' => 'dataset_button_delete',
                    'url' => $this->urlFor('dataSet.column.delete.form', ['id' => $dataSetId, 'colId' => $column->dataSetColumnId]),
                    'text' => __('Delete')
                );
            }
        }

        $this->getState()->template = 'grid';
        $this->getState()->setData($dataSetColumns);
    }

    /**
     * Add form
     * @param int $dataSetId
     */
    public function addForm($dataSetId)
    {
        $dataSet = $this->getFactoryService()->get('DataSetFactory')->getById($dataSetId);

        if (!$this->getUser()->checkEditable($dataSet))
            throw new AccessDeniedException();

        $this->getState()->template = 'dataset-column-form-add';
        $this->getState()->setData([
            'dataSet' => $dataSet,
            'dataTypes' => $this->getFactoryService()->get('DataTypeFactory')->query(),
            'dataSetColumnTypes' => $this->getFactoryService()->get('DataSetColumnTypeFactory')->query(),
            'help' => $this->getHelp()->link('DataSet', 'AddColumn')
        ]);
    }

    /**
     * Add
     * @param $dataSetId
     *
     * @SWG\Post(
     *  path="/dataset/{dataSetId}/column",
     *  operationId="dataSetColumnAdd",
     *  tags={"dataset"},
     *  summary="Add Column",
     *  description="Add a Column to a DataSet",
     *  @SWG\Parameter(
     *      name="dataSetId",
     *      in="path",
     *      description="The DataSet ID",
     *      type="integer",
     *      required=true
     *   ),
     *  @SWG\Parameter(
     *      name="heading",
     *      in="formData",
     *      description="The heading for the Column",
     *      type="string",
     *      required=true
     *   ),
     *  @SWG\Parameter(
     *      name="listContent",
     *      in="formData",
     *      description="A comma separated list of content for drop downs",
     *      type="string",
     *      required=false
     *   ),
     *  @SWG\Parameter(
     *      name="columnOrder",
     *      in="formData",
     *      description="The display order for this column",
     *      type="integer",
     *      required=true
     *   ),
     *  @SWG\Parameter(
     *      name="dataTypeId",
     *      in="formData",
     *      description="The data type ID for this column",
     *      type="integer",
     *      required=true
     *   ),
     *  @SWG\Parameter(
     *      name="dataSetColumnTypeId",
     *      in="formData",
     *      description="The column type for this column",
     *      type="integer",
     *      required=true
     *   ),
     *  @SWG\Parameter(
     *      name="formula",
     *      in="formData",
     *      description="MySQL SELECT syntax formula for this Column if the column type is formula",
     *      type="string",
     *      required=false
     *   ),
     *  @SWG\Response(
     *      response=201,
     *      description="successful operation",
     *      @SWG\Schema(ref="#/definitions/DataSetColumn"),
     *      @SWG\Header(
     *          header="Location",
     *          description="Location of the new record",
     *          type="string"
     *      )
     *  )
     * )
     */
    public function add($dataSetId)
    {
        $dataSet = $this->getFactoryService()->get('DataSetFactory')->getById($dataSetId);

        if (!$this->getUser()->checkEditable($dataSet))
            throw new AccessDeniedException();

        // Create a Column
        $column = new \Xibo\Entity\DataSetColumn();
        $column->setContainer($this->getContainer());
        $column->heading = $this->getSanitizer()->getString('heading');
        $column->listContent = $this->getSanitizer()->getString('listContent');
        $column->columnOrder = $this->getSanitizer()->getInt('columnOrder');
        $column->dataTypeId = $this->getSanitizer()->getInt('dataTypeId');
        $column->dataSetColumnTypeId = $this->getSanitizer()->getInt('dataSetColumnTypeId');
        $column->formula = $this->getSanitizer()->getParam('formula', null);

        $dataSet->assignColumn($column);
        $dataSet->save();

        // Return
        $this->getState()->hydrate([
            'httpStatus' => 201,
            'message' => sprintf(__('Added %s'), $column->heading),
            'id' => $column->dataSetColumnId,
            'data' => $column
        ]);
    }

    /**
     * Edit Form
     * @param $dataSetId
     * @param $dataSetColumnId
     */
    public function editForm($dataSetId, $dataSetColumnId)
    {
        $dataSet = $this->getFactoryService()->get('DataSetFactory')->getById($dataSetId);

        if (!$this->getUser()->checkEditable($dataSet))
            throw new AccessDeniedException();

        $this->getState()->template = 'dataset-column-form-edit';
        $this->getState()->setData([
            'dataSet' => $dataSet,
            'dataSetColumn' => $this->getFactoryService()->get('DataSetColumnFactory')->getById($dataSetColumnId),
            'dataTypes' => $this->getFactoryService()->get('DataTypeFactory')->query(),
            'dataSetColumnTypes' => $this->getFactoryService()->get('DataSetColumnTypeFactory')->query(),
            'help' => $this->getHelp()->link('DataSet', 'EditColumn')
        ]);
    }

    /**
     * Edit
     * @param $dataSetId
     * @param $dataSetColumnId
     *
     * @SWG\Put(
     *  path="/dataset/{dataSetId}/column/{dataSetColumnId}",
     *  operationId="dataSetColumnEdit",
     *  tags={"dataset"},
     *  summary="Edit Column",
     *  description="Edit a Column to a DataSet",
     *  @SWG\Parameter(
     *      name="dataSetId",
     *      in="path",
     *      description="The DataSet ID",
     *      type="integer",
     *      required=true
     *   ),
     *  @SWG\Parameter(
     *      name="dataSetColumnId",
     *      in="path",
     *      description="The Column ID",
     *      type="integer",
     *      required=true
     *   ),
     *  @SWG\Parameter(
     *      name="heading",
     *      in="formData",
     *      description="The heading for the Column",
     *      type="string",
     *      required=true
     *   ),
     *  @SWG\Parameter(
     *      name="listContent",
     *      in="formData",
     *      description="A comma separated list of content for drop downs",
     *      type="string",
     *      required=false
     *   ),
     *  @SWG\Parameter(
     *      name="columnOrder",
     *      in="formData",
     *      description="The display order for this column",
     *      type="integer",
     *      required=true
     *   ),
     *  @SWG\Parameter(
     *      name="dataTypeId",
     *      in="formData",
     *      description="The data type ID for this column",
     *      type="integer",
     *      required=true
     *   ),
     *  @SWG\Parameter(
     *      name="dataSetColumnTypeId",
     *      in="formData",
     *      description="The column type for this column",
     *      type="integer",
     *      required=true
     *   ),
     *  @SWG\Parameter(
     *      name="formula",
     *      in="formData",
     *      description="MySQL SELECT syntax formula for this Column if the column type is formula",
     *      type="string",
     *      required=false
     *   ),
     *  @SWG\Response(
     *      response=201,
     *      description="successful operation",
     *      @SWG\Schema(ref="#/definitions/DataSetColumn"),
     *      @SWG\Header(
     *          header="Location",
     *          description="Location of the new record",
     *          type="string"
     *      )
     *  )
     * )
     */
    public function edit($dataSetId, $dataSetColumnId)
    {
        $dataSet = $this->getFactoryService()->get('DataSetFactory')->getById($dataSetId);

        if (!$this->getUser()->checkEditable($dataSet))
            throw new AccessDeniedException();

        // Column
        $column = $this->getFactoryService()->get('DataSetColumnFactory')->getById($dataSetColumnId);
        $column->heading = $this->getSanitizer()->getString('heading');
        $column->listContent = $this->getSanitizer()->getString('listContent');
        $column->columnOrder = $this->getSanitizer()->getInt('columnOrder');
        $column->dataTypeId = $this->getSanitizer()->getInt('dataTypeId');
        $column->dataSetColumnTypeId = $this->getSanitizer()->getInt('dataSetColumnTypeId');
        $column->formula = $this->getSanitizer()->getParam('formula', null);
        $column->save();

        $dataSet->notify();

        // Return
        $this->getState()->hydrate([
            'message' => sprintf(__('Edited %s'), $column->heading),
            'id' => $column->dataSetColumnId,
            'data' => $column
        ]);
    }

    /**
     * Delete Form
     * @param $dataSetId
     * @param $dataSetColumnId
     */
    public function deleteForm($dataSetId, $dataSetColumnId)
    {
        $dataSet = $this->getFactoryService()->get('DataSetFactory')->getById($dataSetId);

        if (!$this->getUser()->checkDeleteable($dataSet))
            throw new AccessDeniedException();

        $this->getState()->template = 'dataset-column-form-delete';
        $this->getState()->setData([
            'dataSet' => $dataSet,
            'dataSetColumn' => $this->getFactoryService()->get('DataSetColumnFactory')->getById($dataSetColumnId),
            'help' => $this->getHelp()->link('DataSet', 'DeleteColumn')
        ]);
    }

    /**
     * Delete
     * @param $dataSetId
     * @param $dataSetColumnId
     *
     * @SWG\Delete(
     *  path="/dataset/{dataSetId}/column/{dataSetColumnId}",
     *  operationId="dataSetColumnDelete",
     *  tags={"dataset"},
     *  summary="Delete Column",
     *  description="Delete DataSet Column",
     *  @SWG\Parameter(
     *      name="dataSetId",
     *      in="path",
     *      description="The DataSet ID",
     *      type="integer",
     *      required=true
     *   ),
     *  @SWG\Parameter(
     *      name="dataSetColumnId",
     *      in="path",
     *      description="The Column ID",
     *      type="integer",
     *      required=true
     *   ),
     *  @SWG\Response(
     *      response=204,
     *      description="successful operation"
     *  )
     * )
     */
    public function delete($dataSetId, $dataSetColumnId)
    {
        $dataSet = $this->getFactoryService()->get('DataSetFactory')->getById($dataSetId);

        if (!$this->getUser()->checkDeleteable($dataSet))
            throw new AccessDeniedException();

        // Get the column
        $column = $this->getFactoryService()->get('DataSetColumnFactory')->getById($dataSetColumnId);
        $column->delete();

        // Return
        $this->getState()->hydrate([
            'httpStatus' => 204,
            'message' => sprintf(__('Deleted %s'), $column->heading)
        ]);
    }
}