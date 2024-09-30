# simple_api
Simple API code. Stateless. The state is saved in the session.

@Routes

  URL: /reset
  
    Method: POST
    Parameters: none
    Return: 205 []
    Description: used to reset to initial state
  
  URL: /accounts
  
    Method: GET
    Parameters: none
    Return: 200 [{"number": 1}, {"number": 2},{"number": 3}...]
            404 []
          
    Method: POST
    Parameters: {"balance": 25.50}
    Return: 201 {"number": 1}
  
  
  URL: /account/{number}
  
    Method: GET
    Parameters: []
    Return: 200 {"number": 1, "balance": 25.50}
            404 []
          
    Method: PATCH
    Parameters: {"balance": 30.55}
    Return: 201 {"number": 1, "balance": 30.55}
