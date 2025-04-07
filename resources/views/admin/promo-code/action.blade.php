<div class="d-flex">
    @can('edit_promocode')
        <div>
            <a href="{{ route('promo-code.edit', $code->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
        </div>
    @endcan
    @can('delete_promocode')
        <div>
            <form action="{{ route('promo-code.destroy', $code->id) }}" method="POST" class="delete_form">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm delete-btn"
                    onclick="confirmDelete(event)">Delete</button>
            </form>
        </div>
    @endcan
</div>
