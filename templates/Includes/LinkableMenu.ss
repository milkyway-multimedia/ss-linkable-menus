<% if $MenuSet($menuSlug) && $MenuSet($menuSlug).Links %>
    <% with $MenuSet($menuSlug) %>
        <ul class="linkable-menu">
            <% loop $Links %>
                <% include LinkableMenu_Item useDropdowns=1 %>
            <% end_loop %>
        </ul>
    <% end_with %>
<% end_if %>