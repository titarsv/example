<div class="col-md-12 panel select">
    <fieldset>
        <label for="helperText">Варианты</label>
        <textarea class="form-control" name="{{ isset($parent) ? $parent : '' }}[choices]" data-name="choices" cols="30" rows="10">{{ !empty($field->choices) ? $field->choices : '' }}</textarea>
        <p><small class="text-muted">Введите каждый вариант выбора на новую строку.
                <br>
                Для большего контроля, вы можете ввести значение и ярлык по следующему формату:
                <br>
                red : Красный</small></p>
    </fieldset>
</div>