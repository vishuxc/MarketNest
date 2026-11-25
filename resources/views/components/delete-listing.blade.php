@props(['listing'])

<form method="POST" action="{{ route('listings.destroy', $listing) }}" data-delete-listing>
    @csrf
    @method('DELETE')
    <button class="market-button market-button-secondary listing-danger" type="submit">Delete</button>
</form>
