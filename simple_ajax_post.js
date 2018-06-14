$.post(
    '/save-location',
    {
        _token: '{{csrf_token()}}',
        name1: val1,
        name2: val2
    },
    function(result)
    {
        console.log('post complete!');
    }
);
