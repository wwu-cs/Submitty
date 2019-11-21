from .base_testcase import BaseTestCase

class TestLogin(BaseTestCase):
    """
    Test cases around homework library admin page
    """
    def __init__(self, testname):
        super().__init__(testname)

    def test_add_git_source(self):
        """
        Test that clicking the git tab switches tabs
        """
        self.get("/homework/library/manage")
        # Change tabs
        gitTabBtn = self.driver.find_element_by_id("page_2_nav")
        gitTabBtn.click()
        gitUrlInput = self.driver.find_element_by_id("git_url")
        self.assertTrue(gitUrlInput.is_displayed())
        # Submit url
        gitUrlInput.send_keys("https://github.com/Submitty/Submitty.git")
        gitForm = self.driver.find_element_by_id("libraryGitForm")
        gitSubmitBtn = gitForm.find_element(By.XPATH, "//input[@type='submit']")
        gitSubmitBtn.click()
        # wait for redirect. Only redirects on success
        # todo

if __name__ == "__main__":
    import unittest
    unittest.main()
