function changeAPIKey(toAPIKey) {
    console.log(
        document.body.innerHTML = document.body.innerHTML.replace('YOUR' + '_API_KEY', toAPIKey)
    )
}

var s = document.createElement('script')
s.src = document.getElementsByTagName('script')[0].src.replace('APIKey.js', 'highlight.pack.js')
document.head.appendChild(s)

setTimeout(function() {
    var highlight = document.querySelectorAll('pre code')
    for (var i = 0; i < highlight.length; i++) {
        // hljs.highlightBlock(highlight[i])
        // ...
    }
    hljs.initHighlightingOnLoad()
}, 1000)
