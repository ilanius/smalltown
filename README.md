# smalltown

# Tables
#
# friend, post, session, user
#
# Table: friend: fId, uId1, relType, uId2 
#           uId1 => (friend) => uId2        symmetrical   
# for indexing reasons this post will have a reciprocal:  
#           #uId2 => (friend) => #uId1
#
#           uid1 => block/follow => uId2     assymmetrical
#
# *******************************
#  post table
# *******************************
# pId = post Id, auto_incremented 
# ppId = parent post id for comments
# uId owner of post
# puId ?
# pTxt post or comment text, may be emoticon
# pTime timestamp
#

