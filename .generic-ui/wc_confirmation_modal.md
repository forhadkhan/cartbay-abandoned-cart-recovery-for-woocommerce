# WooCommerce Backbone Confirmation Modal

## Overview
This pattern uses WooCommerce's native Backbone modal system (`$.fn.WCBackboneModal`) to display confirmation dialogs before executing actions, such as deleting records, revoking access, or removing licenses. It provides a native, integrated WooCommerce UI experience while gracefully falling back to a standard browser `window.confirm()` if the Backbone modal library is unavailable.

## Purpose
Use this pattern when you need a user to explicitly confirm a destructive or significant action in a WooCommerce settings page or admin screen.

## Architecture and Implementation Details
The pattern consists of three interacting parts:
1. **The Trigger Element**: An HTML element (typically a link `<a>` or `<button>`) with specific `data-modal-*` attributes containing the strings for the modal (title, message, button labels).
2. **The Backbone Template**: An HTML `<script type="text/template">` block rendered in the admin footer that defines the visual structure of the modal.
3. **The JavaScript Coordinator**: A script that intercepts clicks on the trigger element, opens the Backbone modal with the data attributes, and handles the user's confirmation response.

### Data Flow
1. User clicks the trigger element.
2. JavaScript intercepts the click, prevents the default action (navigation), and extracts `data-modal-*` attributes.
3. JavaScript passes the extracted data to `$.fn.WCBackboneModal()`.
4. The Backbone modal library renders the `<script type="text/template">` template, replacing `{{ data.variable }}` placeholders with the provided data.
5. If the user clicks Confirm, the modal's internal JavaScript extracts the URL or action and redirects to it or executes it.

## Step-by-Step Implementation

### 1. Create the Trigger Element
Add a link or button with a specific identifying class (for example, `my-plugin-action-trigger`) and necessary data attributes. Pass the action URL in the `href` or `data-url`.

Use plugin-specific class prefixes for every custom class. Avoid generic names such as `delete-trigger`, `danger-button`, or `modal-confirm` because admin screens often load multiple plugins at once.

```php
$action_url = wp_nonce_url( admin_url( 'admin-post.php?action=my_custom_action' ), 'my_action', 'my_nonce' );

echo '<a href="' . esc_url( $action_url ) . '" 
    class="button button-secondary my-plugin-modal-trigger" 
    data-modal-title="' . esc_attr__( 'Confirm Action', 'my-plugin' ) . '" 
    data-modal-message="' . esc_attr__( 'Are you sure you want to perform this action?', 'my-plugin' ) . '" 
    data-modal-confirm="' . esc_attr__( 'Yes, do it', 'my-plugin' ) . '" 
    data-modal-cancel="' . esc_attr__( 'Cancel', 'my-plugin' ) . '">' . 
    esc_html__( 'Perform Action', 'my-plugin' ) . '</a>';
```

### 2. Output the Backbone Template
Render the template in the WordPress admin footer using the `admin_footer` hook. Ensure the template ID matches what you'll use in the JavaScript implementation.

```php
add_action( 'admin_footer', function() {
    ?>
    <script type="text/template" id="tmpl-my-plugin-confirmation-modal">
        <div class="wc-backbone-modal my-plugin-confirmation-modal">
            <div class="wc-backbone-modal-content">
                <section class="wc-backbone-modal-main" role="main">
                    <header class="wc-backbone-modal-header">
                        <h1>{{ data.title }}</h1>
                        <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                            <span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'my-plugin' ); ?></span>
                        </button>
                    </header>
                    <form>
                        <article>
                            <p>{{ data.message }}</p>
                            <input type="hidden" name="url" value="{{ data.url }}" />
                        </article>
                        <footer>
                            <div class="inner">
                                <button type="button" class="button button-large my-plugin-modal-cancel">{{ data.cancel }}</button>
                                <button type="button" class="button button-large button-primary my-plugin-button-danger-primary my-plugin-modal-confirm" data-url="{{ data.url }}">{{ data.confirm }}</button>
                            </div>
                        </footer>
                    </form>
                </section>
            </div>
        </div>
        <div class="wc-backbone-modal-backdrop modal-close"></div>
    </script>
    <?php
} );
```

### 3. Add the JavaScript Coordinator
Enqueue your JavaScript (or add an inline script) that handles the trigger and modal response. Ensure `wc-backbone-modal` and `wp-util` dependencies are enqueued.

```javascript
jQuery( function( $ ) {
    // 1. Handle Trigger Click
    $( document.body ).on( 'click', '.my-plugin-modal-trigger', function( event ) {
        event.preventDefault();

        var $trigger = $( this );
        var modalData = {
            title: $trigger.data( 'modalTitle' ),
            message: $trigger.data( 'modalMessage' ),
            confirm: $trigger.data( 'modalConfirm' ),
            cancel: $trigger.data( 'modalCancel' ),
            url: $trigger.attr( 'href' )
        };

        // Render Backbone modal if available
        if ( $.fn.WCBackboneModal ) {
            $trigger.WCBackboneModal( {
                template: 'my-plugin-confirmation-modal', // Matches script ID minus 'tmpl-'
                variable: modalData
            } );
            return;
        }

        // Fallback for when the modal script failed to load
        if ( window.confirm( modalData.message ) ) {
            window.location = modalData.url;
        }
    } );

    // 2. Handle Confirm Click inside the Modal
    $( document.body ).on( 'click', '.my-plugin-modal-confirm', function( event ) {
        event.preventDefault();

        var url = $( this ).data( 'url' );

        if ( url ) {
            window.location = url;
        }
    } );

    // 3. Handle Cancel Click inside the Modal
    $( document.body ).on( 'click', '.my-plugin-modal-cancel', function( event ) {
        event.preventDefault();

        // Finds the closest modal close button and triggers a click on it
        $( this ).closest( '.wc-backbone-modal' ).find( '.modal-close' ).first().trigger( 'click' );
    } );
} );
```

## Dependencies
- **PHP/WordPress Hook**: Use the `admin_footer` hook to output the Backbone template markup.
- **JavaScript Dependencies**: `wc-backbone-modal` and `wp-util` must be enqueued via `wp_enqueue_script()`. Without these, the `$.fn.WCBackboneModal` check fails and the fallback `window.confirm` is used instead.

## Usage Examples

### 1. Delete Actions
To delete a specific session, campaign, or configuration record:
- Change the trigger class to `.delete-record-trigger`.
- Set `data-modal-title` to "Delete Record".
- Set `data-modal-message` to "Are you sure you want to permanently delete this record? This cannot be undone."
- Set `data-modal-confirm` to "Delete".
- Consider styling the confirm button with a danger class (e.g., `cartbay-button-danger`).

### 2. Disconnect/Revoke Actions
To disconnect an API or remote integration:
- Set `data-modal-title` to "Revoke Access".
- Set `data-modal-message` to "Revoking access will disconnect this site. Scheduled jobs will fail until reconnected."
- Set `data-modal-confirm` to "Revoke Access".

### 3. Standard Confirmation Workflows
For actions like resetting settings or clearing a cache:
- Set `data-modal-title` to "Reset Settings".
- Set `data-modal-message` to "This will reset all plugin settings to their default values."
- Set `data-modal-confirm` to "Reset Now".

## Styling Requirements
This pattern relies on **native WooCommerce Backbone modal styles**. No custom CSS (like SCSS or JSS) is strictly required to implement the core functionality. The CSS classes used in the template (`wc-backbone-modal`, `wc-backbone-modal-content`, `wc-backbone-modal-header`, `wc-backbone-modal-main`) automatically apply WooCommerce's modal styling from its core admin CSS.

If you need a destructive action button (for example, "Delete", "Revoke Access", or "Remove License"), use a plugin-prefixed danger-primary class on the modal confirm button. Do not rely on a text-only danger class combined with `.button-primary`; that can produce a blue primary background with red text.

Use clear destructive colors for the modal confirm button:

```css
.my-plugin-confirmation-modal footer .inner {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.my-plugin-confirmation-modal .my-plugin-button-danger-primary {
    background: #b32d2e;
    border-color: #b32d2e;
    color: #fff;
}

.my-plugin-confirmation-modal .my-plugin-button-danger-primary:hover,
.my-plugin-confirmation-modal .my-plugin-button-danger-primary:focus {
    background: #8a2424;
    border-color: #8a2424;
    color: #fff;
}
```

Keep the trigger button less visually dominant when it sits in a settings table, such as `.button.button-secondary` plus a text-only danger class. Reserve the red background for the final confirmation action inside the modal.

## Accessibility Considerations
- The Backbone template includes `<span class="screen-reader-text">` for the close button to ensure screen reader visibility.
- The Backbone modal script automatically handles focus trapping and sets appropriate ARIA roles natively within WooCommerce.
- Always use semantic tags (`<header>`, `<article>`, `<footer>`) inside the template to preserve the document structure.
- Provide descriptive, non-ambiguous text for the confirm button instead of a generic "Yes" or "OK".

## Common Pitfalls
- **Missing Dependencies**: Forgetting to enqueue `wc-backbone-modal` and `wp-util` will cause the UI to silently fall back to `window.confirm`.
- **Template ID Mismatch**: The string passed to `template:` in the JS (e.g., `my-plugin-confirmation-modal`) must exactly match the ID of the `<script>` tag **minus** the `tmpl-` prefix (e.g., `<script id="tmpl-my-plugin-confirmation-modal">`).
- **Missing Nonces**: The `data-url` or `href` should always point to an endpoint protected by nonces (e.g., `admin-post.php` actions with `wp_nonce_url`), as modals often trigger state-changing operations.
- **Event Delegation**: Ensure the jQuery `.on()` handlers are attached to `document.body` or a persistent wrapper (not directly to the `.my-modal-confirm` element), because the modal contents are dynamically injected into the DOM when opened.
