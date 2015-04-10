@extends('app')

@section('content')
<div class="container">
	<div class="row">
		<form class="new-prompt-input" method="POST" action="/api/v1/story">
			<h1>Have a story you'd like to tell?</h1>
			<input type="text" name="sentence" class="new-prompt-input-field" placeholder="Just write the first line." />
			<input type="hidden" name="_token" value="{{ csrf_token() }}" />
			<input type="submit" class="button new-prompt-submit" value="&#8594;" />
		</form>
	</div>
</div>
@endsection
