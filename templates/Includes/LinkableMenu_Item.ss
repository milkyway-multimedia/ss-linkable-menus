<li class="<% if $SiteTree %>$SiteTree.LinkingMode<% else %>$LinkingMode<% end_if %><% if $MenuLinks && $useDropdowns %> dropdown<% end_if %>">
    <% if $DoNotLinkInMenus && not $MenuLinks %>
    <span>
    <% else %>
    <a href="<% if $DoNotLinkInMenus %>#<% else_if $AltLink %>$AltLink<% else_if $LinkURL %>$LinkURL<% else %>$RelativeLink<% end_if %>"<% if $MenuLinks && $useDropdowns %>
       data-toggle="dropdown"<% end_if %><% if $RedirectionType == 'External' || $OpenInNewWindow %>
       target="_blank"<% end_if %><% if $menuSiblings %> data-siblings="$menuSiblings"<% end_if %>>
    <% end_if %>
    <% include LinkableMenu_Title %>
    <% if not $DoNotDisplaySubMenu && $MenuLinks %>
        <i></i>
    <% end_if %>

    <% if $DoNotLinkInMenus && not $MenuLinks %>
    </span>
    <% else %>
        </a>
    <% end_if %>

    <% if not $DoNotDisplaySubMenu && $MenuLinks %>
        <ul<% if $useDropdowns %> class="dropdown-menu"<% end_if %> id="{$prefixId}MenuList-{$ID}">
            <% loop $MenuLinks %>
                <% if $canViewInMenu %>
                    <% include LinkableMenu_Item useDropdowns=$Up.useDropdowns,useToggles=$Up.useToggles,prefixId=$Up.prefixId %>
                <% end_if %>
            <% end_loop %>
        </ul>
    <% end_if %>
</li>