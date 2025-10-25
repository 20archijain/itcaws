<?php

// phpcs:ignore
class GetOfflineDropdownOptions
{
    private $dbConn;
    private $commonFunctions;
    private $tableName = "";
    private $condition = "";
    private $arrConfigs = [];
    private $response = [];

    public function __construct($dbConn, $commonFunctions, $arrConfigs)
    {
        $this->dbConn = $dbConn;
        $this->commonFunctions = $commonFunctions;
        $this->arrConfigs = $arrConfigs;
    }

    private function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    private function setCondition($condition)
    {
        $this->condition = $condition;
    }

    private function getConfigResult($arrConfig)
    {
        // Set table name to fetch dropdown options
        $this->setTableName($arrConfig["tableName"]);

        // Set condition
        $this->setCondition(isset($arrConfig["condition"]) ? $arrConfig["condition"] : "");
        if ($this->condition) {
            $this->condition = "WHERE {$this->condition}";
        }

        // return result
        return array(
            "key" => $arrConfig["jsonKey"],
            "dropDownItemList" => $this->getDropDownItemList($arrConfig["tableConfig"]),
            "baseLt" => isset($arrConfig["baseLt"]) ? $arrConfig["baseLt"] : null,
            "baseLg" => isset($arrConfig["baseLg"]) ? $arrConfig["baseLg"] : null,
        );
    }

    private function getDropDownItemList($arrConfig, $upperLevelCondition = "", $upperLevelParams = array())
    {
        // Read config
        $labelColumn = isset($arrConfig["labelColumn"]) && $arrConfig["labelColumn"] ?
            $arrConfig["labelColumn"] : null;
        $valueColumn = isset($arrConfig["valueColumn"]) && $arrConfig["valueColumn"] ?
            $arrConfig["valueColumn"] : null;
        $levelCondition = isset($arrConfig["levelCondition"]) && $arrConfig["levelCondition"] ?
            $arrConfig["levelCondition"] : "";
        $addSubLevelOptions = isset($arrConfig["addSubLevelOptions"]) ?
            $arrConfig["addSubLevelOptions"] : true;
        $arrAddStaticOptions = isset($arrConfig["addStaticOptions"]) &&
            $this->commonFunctions->isNonEmptyArray($arrConfig["addStaticOptions"]) ? $arrConfig["addStaticOptions"] : array();
        $useLabelAsNotNullClause = isset($arrConfig["useLabelAsNotNullClause"]) ?
            $arrConfig["useLabelAsNotNullClause"] : true;
        $distinctOptions = isset($arrConfig["distinctOptions"]) ? $arrConfig["distinctOptions"] : true;
        $groupByColumn = isset($arrConfig["groupByColumn"]) && $arrConfig["groupByColumn"] ?
            $arrConfig["groupByColumn"] : "";
        $orderByColumn = isset($arrConfig["orderByColumn"]) && $arrConfig["orderByColumn"] ?
            $arrConfig["orderByColumn"] : "";
        $addBlankOption = isset($arrConfig["addBlankOption"]) ?
            $arrConfig["addBlankOption"] : true;
        $encodeLabel = isset($arrConfig["encodeLabel"]) ? $arrConfig["encodeLabel"] : true;
        $encodeValue = isset($arrConfig["encodeValue"]) ? $arrConfig["encodeValue"] : true;
        $convertValueIntoInt = isset($arrConfig["convertValueIntoInt"]) ? $arrConfig["convertValueIntoInt"] : false;
        $otherDetails = isset($arrConfig["otherDetails"]) && $this->commonFunctions->isNonEmptyArray($arrConfig["otherDetails"]) ?
            $arrConfig["otherDetails"] : null;

        $arrOptions = array();
        $iOptionsCount = 0;

        if ($labelColumn) {
            // Add blank option as the first option
            if ($addBlankOption) {
                $arrOptions[$iOptionsCount] = $this->getBlankOption($arrConfig, $addSubLevelOptions);
                $iOptionsCount++;
            }

            if ($this->commonFunctions->isNonEmptyArray($arrAddStaticOptions)) {
                foreach ($arrAddStaticOptions as $arrAddStaticOption) {
                    $arrOptions[$iOptionsCount] = $arrAddStaticOption;
                    $iOptionsCount++;
                }
            }

            // Get output option keys
            $optionLabelKey = $this->getLabelKey($arrConfig);
            $optionValueKey = $this->getValueKey($arrConfig);

            // get column names
            $columns = $labelColumn;
            if ($valueColumn && $valueColumn !== $labelColumn) {
                $columns = "$valueColumn, $labelColumn";
            }

            // add other details columns in query
            if ($otherDetails) {
                $arrHtmlTextColumns = isset($otherDetails["htmlTextColumns"]) &&
                    $this->commonFunctions->isNonEmptyArray($otherDetails["htmlTextColumns"]) ? $otherDetails["htmlTextColumns"] : array();
                $outletIdColumn = isset($otherDetails["outletIdColumn"]) &&
                    $otherDetails["outletIdColumn"] ? $otherDetails["outletIdColumn"] : null;
                $addressColumn = isset($otherDetails["addressColumn"]) &&
                    $otherDetails["addressColumn"] ? $otherDetails["addressColumn"] : null;
                $landmarkColumn = isset($otherDetails["landmarkColumn"]) &&
                    $otherDetails["landmarkColumn"] ? $otherDetails["landmarkColumn"] : null;
                $contactNoColumn = isset($otherDetails["contactNoColumn"]) &&
                    $otherDetails["contactNoColumn"] ? $otherDetails["contactNoColumn"] : null;
                $kycDone = isset($otherDetails["kyc_done"]) &&
                    $otherDetails["kyc_done"] ? $otherDetails["kyc_done"] : null;
                $arrListKpiFirst = isset($otherDetails["listKpiFirst"]) &&
                    $otherDetails["listKpiFirst"] ? $otherDetails["listKpiFirst"] : array();
                $arrListKpiSecond = isset($otherDetails["listKpiSecond"]) &&
                    $otherDetails["listKpiSecond"] ? $otherDetails["listKpiSecond"] : array();
                $arrOutletOuterDetails = isset($otherDetails["outletOuterDetails"]) &&
                    $otherDetails["outletOuterDetails"] ? $otherDetails["outletOuterDetails"] : array();
                $showMapIcon = isset($otherDetails["showMapIcon"]) ? $otherDetails["showMapIcon"] : false;
                $ltColumn = isset($otherDetails["ltColumn"]) ? $otherDetails["ltColumn"] : "lt";
                $lgColumn = isset($otherDetails["lgColumn"]) ? $otherDetails["lgColumn"] : "lg";

                // add html columns
                if ($this->commonFunctions->isNonEmptyArray($arrHtmlTextColumns)) {
                    foreach ($arrHtmlTextColumns as $arrHtmlTextConfig) {
                        $htmlTextColumn = isset($arrHtmlTextConfig["column"]) && $arrHtmlTextConfig["column"] ?
                            $arrHtmlTextConfig["column"] : null;
                        if ($htmlTextColumn) {
                            $columns .= ", $htmlTextColumn";
                        }
                    }
                }
                // add listKpiFirst
                if ($this->commonFunctions->isNonEmptyArray($arrListKpiFirst)) {
                    foreach ($arrListKpiFirst as $arrlistKpiFirstConfig) {
                        $listKpiFirstValue = isset($arrlistKpiFirstConfig["value"]) && $arrlistKpiFirstConfig["value"] ?
                            $arrlistKpiFirstConfig["value"] : null;
                        if ($listKpiFirstValue) {
                            $columns .= ", $listKpiFirstValue";
                        }
                    }
                }
                // add listKpiSecond
                if ($this->commonFunctions->isNonEmptyArray($arrListKpiSecond)) {
                    foreach ($arrListKpiSecond as $arrlistKpiSecondConfig) {
                        $listKpiSecondValue = isset($arrlistKpiSecondConfig["value"]) && $arrlistKpiSecondConfig["value"] ?
                            $arrlistKpiSecondConfig["value"] : null;
                        if ($listKpiSecondValue) {
                            $columns .= ", $listKpiSecondValue";
                        }
                    }
                }
                // add OutletOuterDetailsValue
                if ($this->commonFunctions->isNonEmptyArray($arrOutletOuterDetails)) {
                    foreach ($arrOutletOuterDetails as $arrOutletOuterDetailsConfig) {
                        $outletOuterDetailsValue = isset($arrOutletOuterDetailsConfig["value"]) && $arrOutletOuterDetailsConfig["value"] ?
                            $arrOutletOuterDetailsConfig["value"] : null;
                        if ($outletOuterDetailsValue) {
                            $columns .= ", $outletOuterDetailsValue";
                        }
                    }
                }

                // add contact no column
                if ($contactNoColumn) {
                    $columns .= ", $contactNoColumn";
                }

                // add kycDone column
                if ($kycDone) {
                    $columns .= ", $kycDone";
                }

                // add Outlet Id column
                if ($outletIdColumn) {
                    $columns .= ", $outletIdColumn";
                }

                // add Address column
                if ($addressColumn) {
                    $columns .= ", $addressColumn";
                }

                // add Landmark column
                if ($landmarkColumn) {
                    $columns .= ", $landmarkColumn";
                }

                // add lt and lg column
                if ($ltColumn) {
                    $columns .= ", $ltColumn";
                }
                if ($lgColumn) {
                    $columns .= ", $lgColumn";
                }
            }

            // get only distinct options
            if ($distinctOptions) {
                $columns = "DISTINCT $columns";
            }

            // get condition
            $condition = "";
            if ($this->condition) {
                $condition = $this->condition . ($useLabelAsNotNullClause ? " AND $labelColumn IS NOT NULL" : "");
            } elseif ($useLabelAsNotNullClause) {
                $condition = "WHERE $labelColumn IS NOT NULL";
            }

            if ($levelCondition) {
                $condition = $condition . " $levelCondition";
            }

            // append upper level condition
            if ($upperLevelCondition) {
                $condition .= " $upperLevelCondition";
            }

            // get group by clause
            $groupByClause = "";
            if ($groupByColumn) {
                $groupByClause = "GROUP BY $groupByColumn";
            }

            // get order by clause
            $orderByClause = "";
            if ($orderByColumn) {
                $orderByClause = "ORDER BY $orderByColumn";
            }

            // Initialize upper level params
            if (!$upperLevelParams) {
                $upperLevelParams = array();
            }

            // get dropdown options
            $rsRes = null;
            $iNoRows = 0;
            $sQuery = "SELECT $columns FROM {$this->tableName} $condition $groupByClause $orderByClause";
            $this->dbConn->ExecuteSelectQuery($sQuery, $rsRes, $iNoRows, $upperLevelParams);

            if ($iNoRows > 0) {
                $isSublevelPresent = isset($arrConfig["sublevelConfig"]) &&
                    $this->commonFunctions->isNonEmptyArray($arrConfig["sublevelConfig"]);
                $arrSublevelOptions = isset($arrConfig["optionOptionsKey"]) &&
                    $this->commonFunctions->isNonEmptyArray($arrConfig["optionOptionsKey"]) ?
                    $arrConfig["optionOptionsKey"] : array($this->getOptionsKey($arrConfig));

                while ($row = $this->dbConn->GetData($rsRes)) {
                    $arrLabels = explode(" AS ", trim($labelColumn));
                    $sLabel = count($arrLabels) == 1 ? $row[trim($labelColumn)] : $row[trim($arrLabels[1])];
                    $sValue = $valueColumn ? $row[$valueColumn] : $sLabel;

                    // get current condition and pass it into sublevel options
                    $currentLevelCondition = " AND $labelColumn = ?";

                    $label = $encodeLabel ? htmlentities($sLabel) : $sLabel;
                    $value = $encodeValue ? htmlentities($sValue) : $sValue;
                    if ($convertValueIntoInt) {
                        $value = (int) $value;
                    }

                    $arrOptions[$iOptionsCount] = array(
                        $optionLabelKey => $label,
                        $optionValueKey => $value,
                    );

                    // Add other details in the option
                    if ($otherDetails) {
                        $sHtmlCompleteText = "";
                        if ($this->commonFunctions->isNonEmptyArray($arrHtmlTextColumns)) {
                            foreach ($arrHtmlTextColumns as $arrHtmlTextConfig) {
                                $htmlTextLabel = isset($arrHtmlTextConfig["label"]) && $arrHtmlTextConfig["label"] ?
                                    $arrHtmlTextConfig["label"] : "";
                                $htmlTextColumn = isset($arrHtmlTextConfig["column"]) && $arrHtmlTextConfig["column"] ?
                                    $arrHtmlTextConfig["column"] : null;
                                $htmlTextBoldLabel = isset($arrHtmlTextConfig["boldLabel"]) ?
                                    $arrHtmlTextConfig["boldLabel"] : false;

                                if ($htmlTextColumn) {
                                    if ($htmlTextBoldLabel && $htmlTextLabel) {
                                        $htmlTextLabel = "<strong>$htmlTextLabel</strong>";
                                    }
                                    $sHtmlCompleteText .= "$htmlTextLabel {$row[$htmlTextColumn]}, ";
                                }
                            }

                            if ($sHtmlCompleteText) {
                                $sHtmlCompleteText = rtrim($sHtmlCompleteText, ", ");
                                $sHtmlCompleteText = "<div>$sHtmlCompleteText</div>";
                            }
                        }
                        $listFirstKpiValues = array();
                        if ($this->commonFunctions->isNonEmptyArray($arrListKpiFirst)) {
                            foreach ($arrListKpiFirst as $arrListKpiFirstConfig) {
                                $label = isset($arrListKpiFirstConfig["label"]) && $arrListKpiFirstConfig["label"] ?
                                    $arrListKpiFirstConfig["label"] : "";
                                $value = isset($arrListKpiFirstConfig["value"]) && $arrListKpiFirstConfig["value"] ?
                                    $arrListKpiFirstConfig["value"] : null;

                                $listFirstKpiValues[] = array(
                                    "label" => $label,
                                    "value" => $row[$value],
                                );
                            }
                        }
                        $listSecondKpiValues = array();
                        if ($this->commonFunctions->isNonEmptyArray($arrListKpiSecond)) {
                            foreach ($arrListKpiSecond as $arrListKpiSecondConfig) {
                                $label = isset($arrListKpiSecondConfig["label"]) && $arrListKpiSecondConfig["label"] ?
                                    $arrListKpiSecondConfig["label"] : "";
                                $value = isset($arrListKpiSecondConfig["value"]) && $arrListKpiSecondConfig["value"] ?
                                    $arrListKpiSecondConfig["value"] : null;

                                $listSecondKpiValues[] = array(
                                    "label" => $label,
                                    "value" => $row[$value],
                                );
                            }
                        }
                        $outletOuterDetailsValue = array();
                        if ($this->commonFunctions->isNonEmptyArray($arrOutletOuterDetails)) {
                            foreach ($arrOutletOuterDetails as $arrOutletOuterDetailsConfig) {
                                $label = isset($arrOutletOuterDetailsConfig["label"]) && $arrOutletOuterDetailsConfig["label"] ?
                                    $arrOutletOuterDetailsConfig["label"] : "";
                                $value = isset($arrOutletOuterDetailsConfig["value"]) && $arrOutletOuterDetailsConfig["value"] ?
                                    $arrOutletOuterDetailsConfig["value"] : null;

                                $outletOuterDetailsValue[] = array(
                                    "label" => $label,
                                    "value" => $row[$value],
                                );
                            }
                        }

                        $arrOptions[$iOptionsCount]["otherDetails"] = array(
                            "htmlText" => $sHtmlCompleteText,
                            "outletIdColumn" => $outletIdColumn && isset($row[$outletIdColumn]) &&
                                $row[$outletIdColumn] ? $row[$outletIdColumn] : "",
                            "addressColumn" => $addressColumn && isset($row[$addressColumn]) &&
                                $row[$addressColumn] ? $row[$addressColumn] : "",
                            "landmarkColumn" => $landmarkColumn && isset($row[$landmarkColumn]) &&
                                $row[$landmarkColumn] ? $row[$landmarkColumn] : "",
                            "contactNo" => $contactNoColumn && isset($row[$contactNoColumn]) &&
                                $row[$contactNoColumn] ? $row[$contactNoColumn] : "",
                            "kyc_done" => $kycDone && isset($row[$kycDone]) &&
                                $row[$kycDone] ? $row[$kycDone] : 0,
                            "datetimeInMilisec" => $contactNoColumn && isset($row[$contactNoColumn]) &&
                                $row[$contactNoColumn] ? $row[$contactNoColumn] : "",
                            "listKpiFirst" => $listFirstKpiValues,
                            "listKpiSecond" => $listSecondKpiValues,
                            "outletOuterDetails" => $outletOuterDetailsValue,
                            "showMapIcon" => $showMapIcon && isset($row[$ltColumn]) && $row[$ltColumn] ? true : false,
                            "lt" => $ltColumn && $row[$ltColumn] ? 1 * number_format(floatval($row[$ltColumn]), 8) : 0,
                            "lg" => $lgColumn && $row[$lgColumn] ? 1 * number_format(floatval($row[$lgColumn]), 8) : 0,
                        );
                    }

                    // Add each sub level options
                    foreach ($arrSublevelOptions as $sSubLevelOptionsKey) {
                        $options = $isSublevelPresent ?
                            $this->getDropDownItemList(
                                isset($arrConfig["sublevelConfig"][$sSubLevelOptionsKey]) ?
                                    $arrConfig["sublevelConfig"][$sSubLevelOptionsKey] : $arrConfig["sublevelConfig"],
                                $upperLevelCondition . $currentLevelCondition,
                                array_merge($upperLevelParams, array($sLabel))
                            ) : array();

                        // set option properties
                        if ($addSubLevelOptions) {
                            $arrOptions[$iOptionsCount][$sSubLevelOptionsKey] = $options;
                        }
                    }

                    $iOptionsCount++;
                }
            }
        }

        return $arrOptions;
    }

    private function getBlankOption($arrConfig, $addSubLevelOptions)
    {
        $blankOptionLabel = isset($arrConfig["blankOptionLabel"]) ? $arrConfig["blankOptionLabel"] : "Please select";
        $optionLabelKey = $this->getLabelKey($arrConfig);
        $optionValueKey = $this->getValueKey($arrConfig);
        $optionOptionsKey = $this->getOptionsKey($arrConfig);
        $arrOptionOptionsKey = $this->commonFunctions->isNonEmptyArray($optionOptionsKey) ? $optionOptionsKey : array($optionOptionsKey);

        $arrOption = array(
            $optionLabelKey => $blankOptionLabel,
            $optionValueKey => "",
        );

        // Add each sublevel options
        if ($addSubLevelOptions) {
            foreach ($arrOptionOptionsKey as $optionOptionsKey) {
                $arrOption[$optionOptionsKey] = array();
            }
        }

        foreach ($arrConfig as $key => $arrSubconfig) {
            if ($key == "sublevelConfig" && $arrSubconfig) {
                foreach ($arrOptionOptionsKey as $optionOptionsKey) {
                    $sublevelConfig = isset($arrSubconfig[$optionOptionsKey]) ?
                        $arrSubconfig[$optionOptionsKey] : $arrSubconfig;

                    $addBlankOption = !isset($sublevelConfig["addBlankOption"]) || $sublevelConfig["addBlankOption"] ?
                        true : false;
                    if ($addBlankOption) {
                        $addSubLevelOptions = isset($sublevelConfig["addSubLevelOptions"]) ?
                            $sublevelConfig["addSubLevelOptions"] : true;
                        $arrOption[$optionOptionsKey][] = $this->getBlankOption($sublevelConfig, $addSubLevelOptions);
                    }
                }
            }
        }

        return $arrOption;
    }

    private function getLabelKey($arrConfig)
    {
        return isset($arrConfig["optionLabelKey"]) && $arrConfig["optionLabelKey"] ?
            $arrConfig["optionLabelKey"] : "label";
    }

    private function getValueKey($arrConfig)
    {
        return isset($arrConfig["optionValueKey"]) && $arrConfig["optionValueKey"] ?
            $arrConfig["optionValueKey"] : "value";
    }

    private function getOptionsKey($arrConfig)
    {
        return isset($arrConfig["optionOptionsKey"]) && $arrConfig["optionOptionsKey"] ?
            $arrConfig["optionOptionsKey"] : "options";
    }

    public function getDropdownOptions()
    {
        if ($this->commonFunctions->isNonEmptyArray($this->arrConfigs)) {
            foreach ($this->arrConfigs as $arrConfig) {
                $this->response[] = $this->getConfigResult($arrConfig);
            }
        }

        return $this->response;
    }
}
