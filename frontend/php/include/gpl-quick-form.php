<?php /*-*-PHP-*-*/
# Quick HTML_QuickForm clone without the GPL-incompatible license
#
# Copyright (C) 2007  Cliss XXI
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017, 2018, 2022 Ineiev
#
# This file is part of Savane.
#
# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

class GPLQuickForm_Element
{
  private $name = '';
  private $type = NULL;
  private $constant = NULL;
  private $default = NULL;
  private $value = NULL;

  private $frozen = false;

  private $label = '';
  private $error = '';
  private $title = '';

  private $select_options = [];
  public function __construct ($type, $name, $params)
  {
    $this->type = $type;
    $this->name = $name;

    switch($this->type)
      {
      case "header":
      case "password":
      case "submit":
      case "text":
      case "checkbox":
      case "textarea":
        if (isset ($params[0]))
          $this->title = $params[0];
        if (isset ($params[1]))
          $this->label = $params[1];
        break;
      case "hidden":
        if (isset ($params[0]))
          $this->default = $params[0];
        break;
      case "select":
        $this->title = $params[0];
        $this->select_options = $params[1];
        break;
      }
  }

  public function setLabel ($text)
  {
    $this->label = $text;
  }
  public function setText ($text)
  {
    $this->setLabel($text);
  }
  public function setError ($text)
  {
    $this->error = $text;
  }

  public function getValue ()
  {
    if ($this->constant !== NULL)
      return $this->constant;
    if ($this->value !== NULL)
      return $this->value;
    return $this->default;
  }

  public function setValue ($value)
  {
    $this->value = $value;
  }
  public function setDefault ($default)
  {
    $this->default = $default;
  }
  public function setConstant ($constant)
  {
    $this->constant = $constant;
  }
  public function freeze()
  {
    $this->frozen = true;
  }

  private function title_attr ()
  {
    $title = '';
    if ($this->label)
      $title = $this->label;
    if ($this->title)
      $title = $this->title;
    $label = '';
    if ($title)
      $label = 'title="' . htmlspecialchars ($title, ENT_QUOTES) . '" ';
    return $label;
  }

  private function display_checkbox ($value)
  {
    $checked = " ";
    if ($value === NULL)
      $value = '1';
    else
      $checked = ' checked="checked" ';
    $disabled = "";
    if ($this->frozen)
      $disabled = ' disabled="disabled" ';
    print "<p><input type='checkbox' name='{$this->name}' id='{$this->name}' "
      . "value='$value'$checked$disabled/>\n"
      . "<label for='{$this->name}'>{$this->title}</label>\n &nbsp;&nbsp;";
    if (!empty ($this->error))
      print "<span class='error'>$this->error</span>";
    print "</p>\n";
  }
  private function name_attr ()
  {
     return "id='{$this->name}' name='{$this->name}'";
  }
  private function for_attr ()
  {
     return "for='{$this->name}'";
  }
  private function display_text ($value, $esc_val)
  {
    $for_attr = $this->for_attr ();
    $name_attr = $this->name_attr ();
    print '<p>';
    print "<b><label $for_attr>{$this->title}</label></b>&nbsp;\n&nbsp;";
    if ($this->frozen)
      print "$value<input type='hidden' $name_attr value='$esc_val' />\n";
    else
      print "<input type='text' " . $this->title_attr ()
        . "$name_attr value='$esc_val' />\n";
    if (!empty ($this->error))
      print "&nbsp;&nbsp; <span class='error'>$this->error</span>";
    if (!$this->frozen && !empty ($this->label))
      print "<br />\n{$this->label}";
    print "</p>\n";
  }
  public function display ()
  {
    # Don't use $this->value directly, to take constants into account.
    $value = $this->getValue ();
    if ($this->type == 'header')
      {
        print "<h2>{$this->title}</h2>\n";
        return;
      }
    $for_attr = $this->for_attr ();
    $name_attr = $this->name_attr ();
    if ($this->type == 'submit')
      {
        $disabled = "";
        if ($this->frozen)
          $disabled = 'disabled="disabled"';

        print "<p><input type='submit'"
          . " $name_attr value='{$this->title}' $disabled />";
        print "</p>\n";
        return;
      }
    if ($this->type == 'hidden')
      {
        print "<input type='hidden' $name_attr value='$value' />";
        return;
      }
    $esc_val = htmlspecialchars ($value, ENT_QUOTES);
    if ($this->type == 'checkbox')
      {
        $this->display_checkbox ($value);
        return;
      }
    if ($this->type == 'text')
      {
        $this->display_text ($value, $esc_val);
        return;
      }
    print '<p>';
    print "<b><label $for_attr>{$this->title}</label></b>&nbsp;\n&nbsp;";
    if (!empty ($this->error))
      print "<span class='error'>$this->error</span><br />";
    switch ($this->type)
      {
      case 'password':
        if ($this->frozen)
          print "*****";
        else
          print "<input type='password' $name_attr value='$esc_val' />\n";
        break;

      case 'select':
        if ($this->frozen)
          {
            if ($value == NULL)
              break;
            print $this->select_options[$value];
            print "<input type='hidden' $name_attr value='$value' />\n";
            break;
          }
        print "<select $name_attr " . $this->title_attr () . ">\n";
        foreach($this->select_options as $id => $text)
          {
            $selected = '';
            if ($id == $value)
              $selected = " selected='selected'";
            print "<option value='$id'$selected>$text</option>\n";
          }
        print "</select>\n";
        break;

      case 'textarea':
        $readonly = "";
        if ($this->frozen)
          $readonly = " readonly='readonly'";
        print "<br />\n<textarea $name_attr wrap='virtual' "
          . "cols='60' rows='10'$readonly>$esc_val</textarea>\n";
        if (!$this->frozen && !empty ($this->label))
          print "<br />\n{$this->label}";
        break;
      }
    print "</p>\n";
  }
}


class GPLQuickForm
{
  private $name = '';
  private $method = '';
  private $in = array();

  private $jsWarnings_pref = 'The form is not valid';
  private $jsWarnings_post = '';
  private $elements = array();
  private $rules = array();

  public function __construct($name='', $method='post')
  {
    $this->name = $name;
    $this->method = $method;

    switch ($method)
      {
      case "get":
        $this->in = $_GET;
        break;
      case 'post':
      default:
        $this->in = $_POST;
        break;
      }
  }

  public function setJsWarnings($pref, $post)
  {
    $this->jsWarnings_pref = $pref;
    $this->jsWarnings_post = $post;
  }

  public function addElement()
  {
    $arg_list = func_get_args();

    $type = array_shift($arg_list);
    $name = array_shift($arg_list);
    $params = $arg_list;

    if (!is_string($type))
      throw new Exception("Adding elements as objects not supported");

    $this->elements_debug[$name] = array($type, $arg_list);

    $this->elements[$name] = new GPLQuickForm_Element($type, $name, $params);

    // Set the value from $_GET/$_POST if available
    if (isset($this->in[$name]))
      $this->elements[$name]->setValue($this->in[$name]);
  }
  public function getElement($name)
  {
    return $this->elements[$name];
  }


  public function setConstants($constants)
  {
    foreach($constants as $name => $value)
      {
        if (isset($this->elements[$name]))
          $this->elements[$name]->setConstant($value);
      }
  }
  public function setDefaults($defaults)
  {
    foreach($defaults as $name => $value)
      {
        if (isset($this->elements[$name]))
          $this->elements[$name]->setDefault($value);
      }
  }

  public function exportValue($name)
  {
    return $this->elements[$name]->getValue();
  }
  public function exportValues()
  {
    $retval = array();
    foreach ($this->elements as $name => $obj)
      $retval[$name] = $obj->getValue();
    return $retval;
  }

  public function freeze($elts_to_freeze=null)
  {

    if ($elts_to_freeze == NULL)
      $elts_to_freeze = array_keys($this->elements);
    else if (!is_array($elts_to_freeze))
      $elts_to_freeze = array($elts_to_freeze);

    foreach ($elts_to_freeze as $name)
      $this->elements[$name]->freeze();
  }



  public function applyFilter($name, $callback)
  {
    $elt = $this->elements[$name];
    $old_value = $elt->getValue();
    if ($old_value !== NULL)
      {
        $new_value = call_user_func($callback, $old_value);
        $elt->setValue($new_value);
      }
  }

  public function addRule($name, $error_message, $type, $type_param=null,
                          $side='server')
  {
    $this->rules[] = array($name, $error_message, $type, $type_param, $side);
  }
  public function validate()
  {
    $elt_is_valid = array();
    $form_is_valid = true;

    if (empty($this->in))
      # Form not submitted yet.
      return false;

    foreach($this->rules as $rule)
      {
        list ($name, $error_message, $type, $type_param, $side) = $rule;
        if (is_array($name))
          {
            $name_array = $name;
            $name = $name_array[0];
          }
        if (!isset($elt_is_valid[$name]))
          $elt_is_valid[$name] = true;
        $elt = $this->elements[$name];

        if ($elt_is_valid[$name])
          {
            $rule_is_valid = false;
            if (!is_array($name))
              {
                $value = $elt->getValue();
              }
            switch($type)
              {
              case 'callback':
                $callback = $type_param;
                $rule_is_valid = call_user_func($callback, $value);
                break;
              case 'required':
                $rule_is_valid = !empty($value);
                break;
              case 'regex':
                $pattern = $type_param;
                $rule_is_valid = preg_match($pattern, $value) > 0;
                break;
              case 'nonzero':
                $rule_is_valid = !preg_match('/^0/', $value);
                break;
              case 'lettersonly':
                $rule_is_valid = preg_match('/^[a-zA-Z]*$/', $value);
                break;
              case 'alphanumeric':
                $rule_is_valid = preg_match('/^[a-zA-Z0-9]*$/', $value);
                break;
              case 'minlength':
                $rule_is_valid = (strlen($value) >= $type_param);
                break;
              case 'maxlength':
                $rule_is_valid = (strlen($value) <= $type_param);
                break;
              case 'rangelength':
                $rule_is_valid = (strlen($value) >= $type_param[0]
                                  && strlen($value) <= $type_param[1]);
                break;
              case 'compare':
                $name2 = $name_array[1];
                $elt2 = $this->elements[$name2];
                $value2 = $elt2->getValue();
                $rule_is_valid = ($value == $value2);
                break;
              default:
                die("Unsupported rule type: $type");
              }
            if (!$rule_is_valid)
              {
                $form_is_valid = false;
                $elt_is_valid[$name] = false;
                $elt->setError($error_message);
              }
          }
      }
    return $form_is_valid;
  }

  public function display()
  {
    print "<form class='gpl-quick-form' id='$this->name' action='"
      . htmlentities ($_SERVER['PHP_SELF']) . "' method='$this->method'>\n";
    foreach ($this->elements as $element)
      $element->display();
    print "\n</form>\n";
  }
}
?>
