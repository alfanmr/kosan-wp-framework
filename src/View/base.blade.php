<div class="wrap">
    <h1>{{ $title ?? ''}}</h1>
    <div id="poststuff"> 
        @form()
            @hidden("_wpnonce", $nonce)
            @hidden("kosan-$primary", $value[$primary] ?? '')
            <div id="post-body" class="metabox-holder columns-2">
                <!-- main content -->
                <div id="post-body-content" class="meta-box-sortables ui-sortable">
                    <div class="postbox">
                        @foreach($inputs ?? [] as $input) 
                            @switch($input['type'])
                                @case('input')
                                    @input("kosan[".$input['id']."]", $input['label'] ?? $input['id'], $value[$input['id']] ?? '', $input['input_type'] ?? "text", $input['extra'] ?? null)
                                @break
                                @case('select')
                                    @select("kosan[".$input['id']."]", $input['label'] ?? $input['id'])
                                        @foreach($input['option'] as $val => $text)
                                            @item($val, $text, $value[$input['id']] ?? '')
                                        @endforeach
                                    @endselect()
                                @break
                            @endswitch
                        @endforeach
                    </div> 
                </div>
                
                <!-- sidebar -->
                <div id="postbox-container-1" class="postbox-container">
                    <div class="meta-box-sortables">
                        <div class="postbox">
                            <h3>Actions</h3>

                            <div id="major-publishing-actions">

                                <div id="publishing-action">
                                    <span class="spinner"></span>

                                    
                                    <input type="submit" value="Save Changes" name="publish" id="publish" class="button button-primary button-large">
                                </div>

                                <div class="clear"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endform
    </div>
    
</div>