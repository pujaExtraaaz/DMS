@props(['kind'])
<div class="tally-co-actions">
    <a class="btn" data-esc href="{{ tally_route($kind->routeName('index')) }}">Q: Quit</a>
    <button class="btn" type="submit" name="action" value="draft">Save draft</button>
    <button class="btn btn-primary" type="submit" name="action" value="post">A: Accept</button>
</div>
