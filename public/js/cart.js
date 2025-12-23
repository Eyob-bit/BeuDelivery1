<script>
function addToCart(id) {
    fetch("../../user/ajax_add_to_cart.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "id=" + id
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const popup = document.getElementById("cartPopup");
            document.getElementById("cartText").innerText =
                data.name + " added to cart (" + data.count + ")";

            popup.style.bottom = "0";

            setTimeout(() => {
                popup.style.bottom = "-100px";
            }, 3000);
        }
    })
}
</script>
