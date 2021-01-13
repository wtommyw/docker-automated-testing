<?php include(__DIR__ . '/partial.header.php') ?>
<body>
    <? if ( empty($form) ) exit ?>
    <div class="form">
        <div class="form--title">
            <h1><?= $form['title'] ?></h1>
        </div>    
        <div class="form--body">
            <form method="<?= $form['method'] ?>" action="<?= $form['action'] ?>">
                <? foreach($form['fields'] as $field) { ?>
                    <div class="data">
                        <label for="<?= $field['name'] ?>"> <?= $field['label'] ?> </label>

                        <? if ( $field['type'] === 'text' ) { ?>

                            <input type="text" <?= $field['required'] ? 'required' : '' ?>  name="<?= $field['name'] ?>" value="<?= $field['value'] ?>" >

                            <? } else if ( $field['type'] === 'select' ) { ?>

                            <select name="<?= $field['name'] ?>">

                                <? foreach ( $field['options'] as $option ) { ?>
                                    <? $value = is_array($option) ? $option['value'] : $option ?>
                                    <? $label = is_array($option) ? $option['label'] : $value ?>

                                    <option value="<?= $value ?>" <?= $value === $field['selected'] ? 'selected ' : ' '  ?>>
                                        <?= $label ?>
                                    </option>
                                <? } ?>
                                
                            </select>
                            
                            
                            <? } ?>

                    </div>

                <? } ?> 
            </div>
            <div class="form--button">
                <?
                if (isset($form['hidden']))
                    foreach($form['hidden'] as $name => $value)
                        echo "<input type='hidden' name='${name}' value='${value}' '>";
                ?>

                <button type="submit">Next</button>
            </div>
        
        </form>
    </div>
    

        
                
</body>