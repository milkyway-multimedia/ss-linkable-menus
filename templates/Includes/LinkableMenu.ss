<% if $MenuSet($menuSlug) && $MenuSet($menuSlug).Links %>
    <% with $MenuSet($menuSlug) %>
        <ul class="linkable-menu">
            <% loop $Links %>
                <% if $canViewInMenu %>
                    <% include LinkableMenu_Item useDropdowns=1 %>
                <% end_if %>
            <% end_loop %>
        </ul>
    <% end_with %>
<% end_if %>