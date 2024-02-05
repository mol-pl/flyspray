                <div>
                   {!tpl_userselect('assigned_select', null, 'assigned_select', array('onkeypress' => 'return entercheck(event, true)'))}
                   <button type="button" onmouseup="adduserselect('{$baseurl}javascript/callbacks/useradd.php', $('assigned_select').value, 'assigned_to', '{#(L('usernotexist'))}')">
                      +
                   </button>
                   <button type="button" onmouseup="dualSelect('r', '', 'assigned_to')">
                      &ndash;
                   </button>
                   <br />

                   <select size="8" style="width:200px;" name="rassigned_to" onkeypress="deleteuser(event)" id="rassigned_to">
				     <?php if (isset($userlist)): ?>
                     {!tpl_options($userlist)}
					 <?php endif; ?>
					</select>
                   <input type="hidden" value="{Req::val('assigned_to', $old_assigned)}" id="vassigned_to" name="assigned_to" />
				</div>
                <script type="text/javascript">
                function preventSubmit(oEvent)
				{
					if ('function' == typeof oEvent.preventDefault)
					{
						oEvent.preventDefault();
					}
					else
					{
						oEvent.returnValue = false;
					}
					return false;
				}

                function entercheck(e, add)
                {
                    var keynum;
                    keynum = (e.keyCode) ? e.keyCode : e.which;
                    if (keynum == 13) {
                        if (add && $('assigned_select').value) {
                            adduserselect('{$baseurl}javascript/callbacks/useradd.php', $('assigned_select').value, 'assigned_to', '{#(L('usernotexist'))}');
                        }
                        return preventSubmit(e);
                    }
                    return true;
                }

                function deleteuser(e)
                {
                    var keynum;
                    keynum = (e.keyCode) ? e.keyCode : e.which;
                    if (keynum == 46) {
                        dualSelect('r', '', 'assigned_to');
                        return false;
                    }
                    return true;
                }
                remove_0val('rassigned_to');
                fill_userselect('{$baseurl}javascript/callbacks/useradd.php', 'assigned_to');
                </script>