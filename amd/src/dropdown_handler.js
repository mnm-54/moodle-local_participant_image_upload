import $ from "jquery";
export const init = (redirectUrl) => {

    $('.give-attendance').on("click", function () {
        let userId = $(this).attr('id');
        let sessionId = document.getElementById(`select-${userId}`).value;
        // let courseId;
        // if(optionValue) {
        //     let tempArray = optionValue.split('-');
        //     courseId = tempArray[0];
        //     sessionId = tempArray[1];
        // }
        if (sessionId && userId) {

            window.location.replace(
                redirectUrl + "&session_id=" + sessionId + "&id=" + userId
            );
        } else {
            //window.location.replace(redirectUrl);
        }
    });
}